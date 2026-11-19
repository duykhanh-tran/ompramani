"use client";

import { useState, useEffect, useCallback, useRef } from "react";
import {
  PracticeSession,
  StartPracticePayload,
  PracticeProgressData,
} from "@/types/practice";
import {
  getActivePracticeSession,
  startPracticeSession,
  pausePracticeSession,
  resumePracticeSession,
  heartbeatPracticeSession,
  updatePracticeProgress,
  completePracticeSession,
  cancelPracticeSession,
} from "@/lib/api/practice-sessions";
import { ApiError } from "@/lib/api/client";

export function usePracticeSession() {
  const [session, setSession] = useState<PracticeSession | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isOperating, setIsOperating] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [liveDuration, setLiveDuration] = useState<number>(0);

  // Monotonic sequence tracker to prevent stale update errors across async/debounced calls
  const sequenceRef = useRef<number>(0);

  // Sync sequenceRef whenever session changes
  useEffect(() => {
    if (session?.sequence !== undefined) {
      sequenceRef.current = Math.max(sequenceRef.current, session.sequence);
    }
  }, [session?.sequence]);

  // Fetch initial active session on mount
  useEffect(() => {
    let ignore = false;
    getActivePracticeSession()
      .then((active) => {
        if (!ignore) {
          setSession(active);
          if (active?.sequence !== undefined) {
            sequenceRef.current = Math.max(sequenceRef.current, active.sequence);
          }
          setLiveDuration(active ? active.active_duration_seconds : 0);
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (!ignore) {
          setError(
            err instanceof ApiError
              ? err.message
              : "Không thể tải trạng thái phiên tu tập."
          );
          setIsLoading(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, []);

  const refresh = useCallback(async () => {
    setError(null);
    try {
      const active = await getActivePracticeSession();
      setSession(active);
      if (active?.sequence !== undefined) {
        sequenceRef.current = Math.max(sequenceRef.current, active.sequence);
      }
      setLiveDuration(active ? active.active_duration_seconds : 0);
    } catch (err) {
      setError(
        err instanceof ApiError
          ? err.message
          : "Không thể tải trạng thái phiên tu tập."
      );
    }
  }, []);

  const sessionId = session?.id;
  const sessionStatus = session?.status;

  // Heartbeat & smooth 1-second counter when session is active
  useEffect(() => {
    if (!sessionId || sessionStatus !== "active") {
      return;
    }

    const secondTimer = setInterval(() => {
      setLiveDuration((prev) => prev + 1);
    }, 1000);

    const heartbeatTimer = setInterval(async () => {
      try {
        const updated = await heartbeatPracticeSession(sessionId);
        setSession(updated);
        setLiveDuration(updated.active_duration_seconds);
      } catch (err) {
        // If session is no longer active (paused, finished, cancelled) or not found, silently ignore
        if (err instanceof ApiError && (err.status === 422 || err.status === 404)) {
          return;
        }
        console.warn("Heartbeat failed:", err);
      }
    }, 30000);

    return () => {
      clearInterval(secondTimer);
      clearInterval(heartbeatTimer);
    };
  }, [sessionId, sessionStatus]);

  const start = async (payload: StartPracticePayload): Promise<PracticeSession> => {
    setIsOperating(true);
    setError(null);
    try {
      const newSession = await startPracticeSession(payload);
      sequenceRef.current = newSession.sequence || 0;
      setSession(newSession);
      setLiveDuration(newSession.active_duration_seconds);
      return newSession;
    } catch (err) {
      const msg =
        err instanceof ApiError ? err.message : "Không thể bắt đầu phiên tu tập.";
      setError(msg);
      throw err;
    } finally {
      setIsOperating(false);
    }
  };

  const pause = async (): Promise<PracticeSession | null> => {
    if (!session) return null;
    setIsOperating(true);
    setError(null);
    try {
      const updated = await pausePracticeSession(session.id);
      sequenceRef.current = Math.max(sequenceRef.current, updated.sequence);
      setSession(updated);
      setLiveDuration(updated.active_duration_seconds);
      return updated;
    } catch (err) {
      const msg =
        err instanceof ApiError ? err.message : "Không thể tạm dừng phiên tu tập.";
      setError(msg);
      throw err;
    } finally {
      setIsOperating(false);
    }
  };

  const resume = async (): Promise<PracticeSession | null> => {
    if (!session) return null;
    setIsOperating(true);
    setError(null);
    try {
      const updated = await resumePracticeSession(session.id);
      sequenceRef.current = Math.max(sequenceRef.current, updated.sequence);
      setSession(updated);
      setLiveDuration(updated.active_duration_seconds);
      return updated;
    } catch (err) {
      const msg =
        err instanceof ApiError ? err.message : "Không thể tiếp tục phiên tu tập.";
      setError(msg);
      throw err;
    } finally {
      setIsOperating(false);
    }
  };

  const progress = async (
    data: PracticeProgressData
  ): Promise<PracticeSession | null> => {
    if (!session) return null;
    setError(null);
    sequenceRef.current += 1;
    const nextSequence = sequenceRef.current;
    try {
      const updated = await updatePracticeProgress(session.id, {
        progress_data: data,
        sequence: nextSequence,
      });
      sequenceRef.current = Math.max(sequenceRef.current, updated.sequence);
      setSession(updated);
      return updated;
    } catch (err) {
      if (
        err instanceof ApiError &&
        err.status === 422 &&
        err.message?.includes("sequence")
      ) {
        try {
          const latest = await getActivePracticeSession();
          if (latest) {
            sequenceRef.current = Math.max(sequenceRef.current, latest.sequence);
            setSession(latest);
          }
        } catch {
          // ignore
        }
      }
      const msg =
        err instanceof ApiError ? err.message : "Không thể cập nhật tiến trình.";
      setError(msg);
      throw err;
    }
  };

  const complete = async (
    finalData?: PracticeProgressData
  ): Promise<PracticeSession | null> => {
    if (!session) return null;
    setIsOperating(true);
    setError(null);
    try {
      const updated = await completePracticeSession(session.id, {
        progress_data: finalData,
      });
      sequenceRef.current = 0;
      setSession(null);
      setLiveDuration(0);
      return updated;
    } catch (err) {
      const msg =
        err instanceof ApiError
          ? err.message
          : "Không thể hoàn thành phiên tu tập.";
      setError(msg);
      throw err;
    } finally {
      setIsOperating(false);
    }
  };

  const cancel = async (): Promise<PracticeSession | null> => {
    if (!session) return null;
    setIsOperating(true);
    setError(null);
    try {
      const updated = await cancelPracticeSession(session.id);
      sequenceRef.current = 0;
      setSession(null);
      setLiveDuration(0);
      return updated;
    } catch (err) {
      const msg =
        err instanceof ApiError ? err.message : "Không thể hủy phiên tu tập.";
      setError(msg);
      throw err;
    } finally {
      setIsOperating(false);
    }
  };

  return {
    session,
    liveDuration,
    isLoading,
    isOperating,
    error,
    start,
    pause,
    resume,
    progress,
    complete,
    cancel,
    refresh,
  };
}
