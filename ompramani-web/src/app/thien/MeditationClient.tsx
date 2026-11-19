"use client";

import React, { useState, useEffect, useCallback } from "react";
import { useSearchParams } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { usePracticeSession } from "@/hooks/usePracticeSession";
import { MeditationPreparation } from "@/components/meditation/MeditationPreparation";
import { MeditationActiveSession } from "@/components/meditation/MeditationActiveSession";
import { MeditationCompletion } from "@/components/meditation/MeditationCompletion";
import { MeditationConflictModal } from "@/components/meditation/MeditationConflictModal";
import { initAudioContext, playSingingBowl } from "@/lib/utils/bellAudio";
import { PracticeSession } from "@/types/practice";

export function MeditationClient() {
  const searchParams = useSearchParams();
  const { user, isAuthenticated } = useAuth();
  const {
    session,
    liveDuration,
    isOperating,
    start,
    pause,
    resume,
    complete,
    cancel,
  } = usePracticeSession();

  // Parse duration query parameter (e.g. ?duration=15)
  const initialDurationFromUrl = (() => {
    const param = searchParams.get("duration");
    if (param) {
      const parsed = parseInt(param, 10);
      if (!isNaN(parsed) && parsed >= 1 && parsed <= 180) {
        return parsed;
      }
    }
    return 15;
  })();

  const [customDuration, setCustomDuration] = useState<number | null>(null);
  const [soundEnabled, setSoundEnabled] = useState<boolean>(true);
  const [isCompleted, setIsCompleted] = useState<boolean>(false);
  const [conflictSession, setConflictSession] = useState<PracticeSession | null>(null);
  const [completedSeconds, setCompletedSeconds] = useState<number>(0);

  // Guest mode timer state
  const [guestActive, setGuestActive] = useState<boolean>(false);
  const [guestPaused, setGuestPaused] = useState<boolean>(false);
  const [guestElapsedSeconds, setGuestElapsedSeconds] = useState<number>(0);

  // Planned duration in minutes
  const sessionPlannedMinutes = session?.progress_data?.planned_duration_seconds
    ? Math.round(session.progress_data.planned_duration_seconds / 60)
    : null;

  const durationMinutes =
    customDuration ?? sessionPlannedMinutes ?? initialDurationFromUrl;

  // Derive current screen declaratively
  const userSessionActive = Boolean(
    session &&
      session.practice_type === "meditation" &&
      (session.status === "active" || session.status === "paused")
  );

  const isActiveScreen = !isCompleted && (guestActive || userSessionActive);
  const isCompletedScreen = isCompleted;

  // Guest local timer progression
  useEffect(() => {
    if (!guestActive || guestPaused) return;

    const timer = setInterval(() => {
      setGuestElapsedSeconds((prev) => prev + 1);
    }, 1000);

    return () => clearInterval(timer);
  }, [guestActive, guestPaused]);

  // Handle start session
  const handleStart = async () => {
    initAudioContext();

    // Check conflict if user has another open session of non-meditation type
    if (
      isAuthenticated &&
      session &&
      (session.status === "active" || session.status === "paused")
    ) {
      if (session.practice_type !== "meditation") {
        setConflictSession(session);
        return;
      }
    }

    if (soundEnabled) {
      playSingingBowl("start");
    }

    setIsCompleted(false);

    if (isAuthenticated) {
      try {
        await start({
          practice_type: "meditation",
          progress_data: {
            planned_duration_seconds: durationMinutes * 60,
          },
        });
      } catch (err) {
        console.error("Failed to start meditation practice session:", err);
      }
    } else {
      // Guest mode
      setGuestElapsedSeconds(0);
      setGuestPaused(false);
      setGuestActive(true);
    }
  };

  // Handle pause
  const handlePause = async () => {
    if (isAuthenticated && session) {
      try {
        await pause();
      } catch (err) {
        console.error("Failed to pause meditation session:", err);
      }
    } else {
      setGuestPaused(true);
    }
  };

  // Handle resume
  const handleResume = async () => {
    if (isAuthenticated && session) {
      try {
        await resume();
      } catch (err) {
        console.error("Failed to resume meditation session:", err);
      }
    } else {
      setGuestPaused(false);
    }
  };

  // Handle complete session
  const handleComplete = useCallback(async () => {
    if (soundEnabled) {
      playSingingBowl("end");
    }

    if (isAuthenticated && session) {
      try {
        const finalSec =
          liveDuration > 0 ? liveDuration : session.active_duration_seconds;
        await complete({
          planned_duration_seconds: durationMinutes * 60,
          completed_early: finalSec < durationMinutes * 60,
        });
        setCompletedSeconds(finalSec);
      } catch (err) {
        console.error("Failed to complete meditation session:", err);
        setCompletedSeconds(liveDuration);
      }
    } else {
      setGuestActive(false);
      setCompletedSeconds(guestElapsedSeconds);
    }

    setIsCompleted(true);
  }, [
    isAuthenticated,
    session,
    liveDuration,
    durationMinutes,
    soundEnabled,
    complete,
    guestElapsedSeconds,
  ]);

  // Handle cancel session
  const handleCancel = async () => {
    if (isAuthenticated && session) {
      try {
        await cancel();
      } catch (err) {
        console.error("Failed to cancel meditation session:", err);
      }
    } else {
      setGuestActive(false);
      setGuestPaused(false);
      setGuestElapsedSeconds(0);
    }
    setIsCompleted(false);
  };

  // Conflict resolution: finish old session & start meditation
  const handleResolveFinishOld = async () => {
    try {
      await complete();
      setConflictSession(null);
      await handleStart();
    } catch (err) {
      console.error("Failed to finish old session:", err);
    }
  };

  // Conflict resolution: cancel old session & start meditation
  const handleResolveCancelOld = async () => {
    try {
      await cancel();
      setConflictSession(null);
      await handleStart();
    } catch (err) {
      console.error("Failed to cancel old session:", err);
    }
  };

  const activeElapsed = isAuthenticated ? liveDuration : guestElapsedSeconds;

  return (
    <>
      {!isActiveScreen && !isCompletedScreen && (
        <MeditationPreparation
          durationMinutes={durationMinutes}
          onSelectDuration={setCustomDuration}
          soundEnabled={soundEnabled}
          onToggleSound={() => setSoundEnabled(!soundEnabled)}
          onStart={handleStart}
          isStarting={isOperating}
        />
      )}

      {isActiveScreen && (
        <MeditationActiveSession
          plannedDurationSeconds={durationMinutes * 60}
          activeDurationSeconds={activeElapsed}
          isPaused={isAuthenticated ? session?.status === "paused" : guestPaused}
          soundEnabled={soundEnabled}
          onToggleSound={() => setSoundEnabled(!soundEnabled)}
          onPause={handlePause}
          onResume={handleResume}
          onComplete={handleComplete}
          onCancel={handleCancel}
          isOperating={isOperating}
        />
      )}

      {isCompletedScreen && (
        <MeditationCompletion
          durationSeconds={completedSeconds}
          plannedMinutes={durationMinutes}
          user={user}
          onNewSession={() => {
            setIsCompleted(false);
            setGuestElapsedSeconds(0);
            setGuestActive(false);
            setGuestPaused(false);
          }}
        />
      )}

      {conflictSession && (
        <MeditationConflictModal
          session={conflictSession}
          onFinishOldAndStart={handleResolveFinishOld}
          onCancelOldAndStart={handleResolveCancelOld}
          onDismiss={() => setConflictSession(null)}
          isLoading={isOperating}
        />
      )}
    </>
  );
}
