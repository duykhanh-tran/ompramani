"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useAuth } from "@/context/AuthContext";
import { usePracticeSession } from "@/hooks/usePracticeSession";
import { useInstrumentPlayer } from "@/hooks/useInstrumentPlayer";
import { InstrumentHeader } from "@/components/instruments/InstrumentHeader";
import { BellSection } from "@/components/instruments/BellSection";
import { WoodenFishSection } from "@/components/instruments/WoodenFishSection";
import { InstrumentSettingsModal } from "@/components/instruments/InstrumentSettingsModal";
import { InstrumentSessionBar } from "@/components/instruments/InstrumentSessionBar";
import { InstrumentCompletionModal } from "@/components/instruments/InstrumentCompletionModal";

export function InstrumentsClient() {
  const { isAuthenticated } = useAuth();
  const {
    session,
    liveDuration,
    isOperating,
    start,
    progress,
    complete,
    cancel,
  } = usePracticeSession();

  // Modals state
  const [isSettingsOpen, setIsSettingsOpen] = useState<boolean>(false);
  const [isCompletionOpen, setIsCompletionOpen] = useState<boolean>(false);
  const [completionData, setCompletionData] = useState<{
    bellCount: number;
    woodenFishCount: number;
    duration: number;
    completedAt: string;
  } | null>(null);

  // Sync state
  const [syncStatus, setSyncStatus] = useState<
    "saved" | "syncing" | "unsynced" | "guest"
  >("guest");

  // Determine session roles: Standalone vs Support Mode
  const isStandaloneActive = Boolean(
    session &&
      session.practice_type === "bell_wooden_fish" &&
      (session.status === "active" || session.status === "paused")
  );

  const isSupportMode = Boolean(
    session &&
      session.practice_type !== "bell_wooden_fish" &&
      (session.status === "active" || session.status === "paused")
  );

  // Sync queue refs
  const lastSyncedBellRef = useRef<number>(0);
  const lastSyncedFishRef = useRef<number>(0);
  const isSyncingRef = useRef<boolean>(false);
  const pendingSyncRef = useRef<{ bell: number; fish: number } | null>(null);
  const syncTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Synchronize counts to server
  const syncToServer = useCallback(
    async (bell: number, fish: number) => {
      if (!isAuthenticated || !session || session.status !== "active") return;
      if (
        bell === lastSyncedBellRef.current &&
        fish === lastSyncedFishRef.current
      ) {
        return;
      }

      if (isSyncingRef.current) {
        pendingSyncRef.current = { bell, fish };
        return;
      }

      isSyncingRef.current = true;
      let target: { bell: number; fish: number } | null = { bell, fish };

      try {
        while (
          target !== null &&
          (target.bell !== lastSyncedBellRef.current ||
            target.fish !== lastSyncedFishRef.current)
        ) {
          const currentTarget = target;
          target = null;
          pendingSyncRef.current = null;

          setSyncStatus("syncing");
          await progress({
            bell_count: currentTarget.bell,
            wooden_fish_count: currentTarget.fish,
          });

          lastSyncedBellRef.current = currentTarget.bell;
          lastSyncedFishRef.current = currentTarget.fish;
          setSyncStatus("saved");

          const queued = pendingSyncRef.current as { bell: number; fish: number } | null;
          if (
            queued !== null &&
            (queued.bell !== lastSyncedBellRef.current ||
              queued.fish !== lastSyncedFishRef.current)
          ) {
            target = queued;
          }
        }
      } catch (err) {
        console.warn("Failed to sync instrument counts:", err);
        setSyncStatus("unsynced");
      } finally {
        isSyncingRef.current = false;
      }
    },
    [isAuthenticated, session, progress]
  );

  const scheduleSync = useCallback(
    (bell: number, fish: number) => {
      if (!isStandaloneActive) {
        setSyncStatus(isAuthenticated ? "unsynced" : "guest");
        return;
      }

      setSyncStatus("unsynced");

      // Batch sync: immediate if 10 strikes accumulated, otherwise 3s debounce
      const accumulated =
        bell - lastSyncedBellRef.current + (fish - lastSyncedFishRef.current);

      if (accumulated >= 10) {
        if (syncTimeoutRef.current) {
          clearTimeout(syncTimeoutRef.current);
          syncTimeoutRef.current = null;
        }
        syncToServer(bell, fish);
        return;
      }

      if (syncTimeoutRef.current) {
        clearTimeout(syncTimeoutRef.current);
      }

      syncTimeoutRef.current = setTimeout(() => {
        syncToServer(bell, fish);
      }, 3000);
    },
    [isStandaloneActive, isAuthenticated, syncToServer]
  );

  // Audio & Rhythm Controller
  const {
    preferences,
    updatePreferences,
    strikeBellManually,
    strikeWoodenFishManually,
    isBellStriking,
    isFishStriking,
    isBellAutoActive,
    isFishAutoActive,
    toggleAutoBell,
    toggleAutoWoodenFish,
    stopAllAuto,
    bellCount,
    woodenFishCount,
    resetCounts,
    setInitialCounts,
  } = useInstrumentPlayer((_type, currentBell, currentFish) => {
    scheduleSync(currentBell, currentFish);
  });

  // Restore counts from active standalone session on mount
  const isRestoredRef = useRef<boolean>(false);
  useEffect(() => {
    if (isRestoredRef.current) return;

    if (session && session.practice_type === "bell_wooden_fish") {
      isRestoredRef.current = true;
      const initialBell = Number(session.progress_data?.bell_count || 0);
      const initialFish = Number(session.progress_data?.wooden_fish_count || 0);

      lastSyncedBellRef.current = initialBell;
      lastSyncedFishRef.current = initialFish;

      queueMicrotask(() => {
        setInitialCounts(initialBell, initialFish);
        setSyncStatus("saved");
      });
    }
  }, [session, setInitialCounts]);

  // Session Handlers
  const handleStartStandaloneSession = async () => {
    if (!isAuthenticated) return;

    stopAllAuto();
    try {
      await start({
        practice_type: "bell_wooden_fish",
        progress_data: {
          bell_count: bellCount,
          wooden_fish_count: woodenFishCount,
        },
      });
      lastSyncedBellRef.current = bellCount;
      lastSyncedFishRef.current = woodenFishCount;
      setSyncStatus("saved");
    } catch (err) {
      console.error("Failed to start standalone instrument session:", err);
    }
  };

  const handleCompleteStandaloneSession = async () => {
    if (syncTimeoutRef.current) {
      clearTimeout(syncTimeoutRef.current);
      syncTimeoutRef.current = null;
    }
    stopAllAuto();

    const currentDuration = liveDuration;
    const finalBell = bellCount;
    const finalFish = woodenFishCount;

    try {
      await complete({
        bell_count: finalBell,
        wooden_fish_count: finalFish,
      });

      setCompletionData({
        bellCount: finalBell,
        woodenFishCount: finalFish,
        duration: currentDuration,
        completedAt: new Date().toISOString(),
      });
      setIsCompletionOpen(true);
      setIsSettingsOpen(false);
      resetCounts();
      setSyncStatus("saved");
    } catch (err) {
      console.error("Failed to complete instrument session:", err);
    }
  };

  const handleCancelStandaloneSession = async () => {
    if (syncTimeoutRef.current) {
      clearTimeout(syncTimeoutRef.current);
      syncTimeoutRef.current = null;
    }
    stopAllAuto();

    try {
      await cancel();
      resetCounts();
      setSyncStatus("guest");
    } catch (err) {
      console.error("Failed to cancel instrument session:", err);
    }
  };

  return (
    <div className="min-h-screen bg-[#fbf9f5] flex flex-col justify-between relative overflow-hidden select-none">
      {/* Background Dot Grid Pattern (Matching Reference Image) */}
      <div
        className="absolute inset-0 pointer-events-none opacity-45"
        style={{
          backgroundImage: "radial-gradient(#d1c7b7 1.5px, transparent 1.5px)",
          backgroundSize: "28px 28px",
        }}
      />

      {/* 1. Header Bar */}
      <InstrumentHeader
        onOpenSettings={() => setIsSettingsOpen(true)}
        syncStatus={syncStatus}
        isRecording={isStandaloneActive}
      />

      {/* 2. Main Instrument Canvas: Chuông tay trái, Mõ tay phải */}
      <main id="main-content" className="flex-1 flex flex-col items-center justify-center px-3 sm:px-6 py-6 sm:py-10 relative z-10 max-w-5xl mx-auto w-full">
        <div className="grid grid-cols-2 gap-4 sm:gap-10 md:gap-16 items-center justify-center w-full max-w-4xl">
          {/* Tay Trái: Chuông bát Phật giáo */}
          <div className="flex flex-col items-center justify-center w-full">
            <BellSection
              onStrike={strikeBellManually}
              isStriking={isBellStriking}
              isAutoActive={isBellAutoActive}
              onToggleAuto={toggleAutoBell}
              intervalSeconds={preferences.bellInterval}
              onSelectInterval={(sec) => updatePreferences({ bellInterval: sec })}
            />
          </div>

          {/* Tay Phải: Mõ gỗ tụng kinh */}
          <div className="flex flex-col items-center justify-center w-full">
            <WoodenFishSection
              onStrike={strikeWoodenFishManually}
              isStriking={isFishStriking}
              isAutoActive={isFishAutoActive}
              onToggleAuto={toggleAutoWoodenFish}
              intervalSeconds={preferences.woodenFishInterval}
              onSelectInterval={(sec) =>
                updatePreferences({ woodenFishInterval: sec })
              }
            />
          </div>
        </div>

        {/* Subtle Bottom Instruction */}
        <p className="mt-6 sm:mt-10 text-xs font-body-sm text-outline/80 tracking-wide text-center">
          Chạm vào chuông (trái) hoặc mõ (phải) để bắt đầu gõ
        </p>
      </main>

      {/* 3. Floating Bottom Session Bar */}
      <InstrumentSessionBar
        bellCount={bellCount}
        woodenFishCount={woodenFishCount}
        isStandaloneActive={isStandaloneActive}
        isSupportMode={isSupportMode}
        durationSeconds={liveDuration}
        syncStatus={syncStatus}
        onComplete={handleCompleteStandaloneSession}
        onOpenSettings={() => setIsSettingsOpen(true)}
        isOperating={isOperating}
      />

      {/* 4. Settings & Volume Modal */}
      <InstrumentSettingsModal
        isOpen={isSettingsOpen}
        onClose={() => setIsSettingsOpen(false)}
        preferences={preferences}
        onUpdatePreferences={updatePreferences}
        isAuthenticated={isAuthenticated}
        activeSession={session}
        isStandaloneActive={isStandaloneActive}
        isSupportMode={isSupportMode}
        onStartStandaloneSession={handleStartStandaloneSession}
        onCompleteStandaloneSession={handleCompleteStandaloneSession}
        onCancelStandaloneSession={handleCancelStandaloneSession}
        isOperating={isOperating}
        bellCount={bellCount}
        woodenFishCount={woodenFishCount}
        onResetCounts={resetCounts}
      />

      {/* 5. Completion Summary Modal */}
      {completionData && (
        <InstrumentCompletionModal
          isOpen={isCompletionOpen}
          onClose={() => setIsCompletionOpen(false)}
          bellCount={completionData.bellCount}
          woodenFishCount={completionData.woodenFishCount}
          durationSeconds={completionData.duration}
          completedAt={completionData.completedAt}
          onContinue={() => {
            setIsCompletionOpen(false);
            setCompletionData(null);
          }}
        />
      )}
    </div>
  );
}
