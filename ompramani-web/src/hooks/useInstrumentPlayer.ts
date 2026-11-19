"use client";

import { useState, useEffect, useRef, useCallback } from "react";
import {
  getInstrumentAudioEngine,
} from "@/lib/audio/instrument-engine";
import {
  InstrumentPreferences,
  DEFAULT_INSTRUMENT_PREFERENCES,
  loadInstrumentPreferences,
  saveInstrumentPreferences,
} from "@/lib/practice/instruments";

export interface UseInstrumentPlayerReturn {
  preferences: InstrumentPreferences;
  updatePreferences: (partial: Partial<InstrumentPreferences>) => void;
  // Strikes
  strikeBellManually: () => void;
  strikeWoodenFishManually: () => void;
  // Visual feedback indicators
  isBellStriking: boolean;
  isFishStriking: boolean;
  // Auto Mode States
  isBellAutoActive: boolean;
  isFishAutoActive: boolean;
  toggleAutoBell: () => void;
  toggleAutoWoodenFish: () => void;
  stopAllAuto: () => void;
  // Strike counts
  bellCount: number;
  woodenFishCount: number;
  resetCounts: () => void;
  setInitialCounts: (bell: number, fish: number) => void;
}

export function useInstrumentPlayer(
  onStrikeRecorded?: (
    type: "bell" | "wooden_fish",
    currentBellCount: number,
    currentWoodenFishCount: number
  ) => void
): UseInstrumentPlayerReturn {
  const [preferences, setPreferences] = useState<InstrumentPreferences>(
    DEFAULT_INSTRUMENT_PREFERENCES
  );

  useEffect(() => {
    setPreferences(loadInstrumentPreferences());
  }, []);

  const [isBellAutoActive, setIsBellAutoActive] = useState<boolean>(false);
  const [isFishAutoActive, setIsFishAutoActive] = useState<boolean>(false);

  const [bellCount, setBellCount] = useState<number>(0);
  const [woodenFishCount, setWoodenFishCount] = useState<number>(0);

  const [isBellStriking, setIsBellStriking] = useState<boolean>(false);
  const [isFishStriking, setIsFishStriking] = useState<boolean>(false);

  // References for live closures inside audio schedulers
  const prefsRef = useRef<InstrumentPreferences>(preferences);
  useEffect(() => {
    prefsRef.current = preferences;
  }, [preferences]);

  const onStrikeRef = useRef(onStrikeRecorded);
  useEffect(() => {
    onStrikeRef.current = onStrikeRecorded;
  }, [onStrikeRecorded]);

  const bellCountRef = useRef<number>(0);
  useEffect(() => {
    bellCountRef.current = bellCount;
  }, [bellCount]);

  const fishCountRef = useRef<number>(0);
  useEffect(() => {
    fishCountRef.current = woodenFishCount;
  }, [woodenFishCount]);

  // Audio Engine sync on volume/mute change
  useEffect(() => {
    const engine = getInstrumentAudioEngine();
    engine.setMasterVolume(preferences.masterVolume);
    engine.setBellVolume(preferences.bellVolume);
    engine.setWoodenFishVolume(preferences.woodenFishVolume);
    engine.setMuted(preferences.isMuted);
  }, [
    preferences.masterVolume,
    preferences.bellVolume,
    preferences.woodenFishVolume,
    preferences.isMuted,
  ]);

  const triggerHaptic = useCallback(() => {
    if (
      prefsRef.current.hapticEnabled &&
      typeof navigator !== "undefined" &&
      navigator.vibrate
    ) {
      try {
        navigator.vibrate(25);
      } catch {
        // ignore
      }
    }
  }, []);

  // Visual pulse triggers
  const triggerBellVisual = useCallback(() => {
    setIsBellStriking(true);
    setTimeout(() => setIsBellStriking(false), 280);
  }, []);

  const triggerFishVisual = useCallback(() => {
    setIsFishStriking(true);
    setTimeout(() => setIsFishStriking(false), 160);
  }, []);

  // 1. Manual Strike Bell
  const strikeBellManually = useCallback(() => {
    const engine = getInstrumentAudioEngine();
    engine.unlock();
    engine.strikeBell();
    triggerHaptic();
    triggerBellVisual();

    setBellCount((prev) => {
      const next = prev + 1;
      onStrikeRef.current?.("bell", next, fishCountRef.current);
      return next;
    });
  }, [triggerHaptic, triggerBellVisual]);

  // 2. Manual Strike Wooden Fish
  const strikeWoodenFishManually = useCallback(() => {
    const engine = getInstrumentAudioEngine();
    engine.unlock();
    engine.strikeWoodenFish();
    triggerHaptic();
    triggerFishVisual();

    setWoodenFishCount((prev) => {
      const next = prev + 1;
      onStrikeRef.current?.("wooden_fish", bellCountRef.current, next);
      return next;
    });
  }, [triggerHaptic, triggerFishVisual]);

  // 3. Automated Schedulers
  // Bell Lookahead Scheduler
  const bellNextStrikeTimeRef = useRef<number>(0);
  const bellTimerRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!isBellAutoActive) {
      if (bellTimerRef.current) {
        clearInterval(bellTimerRef.current);
        bellTimerRef.current = null;
      }
      return;
    }

    const engine = getInstrumentAudioEngine();
    engine.unlock();
    const ctx = engine.getAudioContext();
    if (!ctx) return;

    // Schedule immediate first strike if starting
    bellNextStrikeTimeRef.current = ctx.currentTime;

    bellTimerRef.current = setInterval(() => {
      if (document.visibilityState === "hidden") {
        // Tab backgrounded: advance next strike to now to prevent backlog burst
        bellNextStrikeTimeRef.current = ctx.currentTime + prefsRef.current.bellInterval;
        return;
      }

      const lookahead = 0.1; // 100ms lookahead
      const now = ctx.currentTime;

      if (bellNextStrikeTimeRef.current < now + lookahead) {
        const offset = Math.max(0, bellNextStrikeTimeRef.current - now);
        engine.strikeBell(offset);

        // Schedule visual and count update
        setTimeout(() => {
          triggerBellVisual();
          triggerHaptic();
          setBellCount((prev) => {
            const next = prev + 1;
            onStrikeRef.current?.("bell", next, fishCountRef.current);
            return next;
          });
        }, offset * 1000);

        bellNextStrikeTimeRef.current += prefsRef.current.bellInterval;
      }
    }, 30);

    return () => {
      if (bellTimerRef.current) {
        clearInterval(bellTimerRef.current);
        bellTimerRef.current = null;
      }
    };
  }, [isBellAutoActive, triggerBellVisual, triggerHaptic]);

  // Wooden Fish Lookahead Scheduler
  const fishNextStrikeTimeRef = useRef<number>(0);
  const fishTimerRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!isFishAutoActive) {
      if (fishTimerRef.current) {
        clearInterval(fishTimerRef.current);
        fishTimerRef.current = null;
      }
      return;
    }

    const engine = getInstrumentAudioEngine();
    engine.unlock();
    const ctx = engine.getAudioContext();
    if (!ctx) return;

    fishNextStrikeTimeRef.current = ctx.currentTime;

    fishTimerRef.current = setInterval(() => {
      if (document.visibilityState === "hidden") {
        fishNextStrikeTimeRef.current = ctx.currentTime + prefsRef.current.woodenFishInterval;
        return;
      }

      const lookahead = 0.08;
      const now = ctx.currentTime;

      if (fishNextStrikeTimeRef.current < now + lookahead) {
        const offset = Math.max(0, fishNextStrikeTimeRef.current - now);
        engine.strikeWoodenFish(offset);

        setTimeout(() => {
          triggerFishVisual();
          triggerHaptic();
          setWoodenFishCount((prev) => {
            const next = prev + 1;
            onStrikeRef.current?.("wooden_fish", bellCountRef.current, next);
            return next;
          });
        }, offset * 1000);

        fishNextStrikeTimeRef.current += prefsRef.current.woodenFishInterval;
      }
    }, 25);

    return () => {
      if (fishTimerRef.current) {
        clearInterval(fishTimerRef.current);
        fishTimerRef.current = null;
      }
    };
  }, [isFishAutoActive, triggerFishVisual, triggerHaptic]);

  // Clean up all timers and stop audio on unmount
  useEffect(() => {
    return () => {
      if (bellTimerRef.current) clearInterval(bellTimerRef.current);
      if (fishTimerRef.current) clearInterval(fishTimerRef.current);
    };
  }, []);

  const toggleAutoBell = useCallback(() => {
    setIsBellAutoActive((prev) => !prev);
  }, []);

  const toggleAutoWoodenFish = useCallback(() => {
    setIsFishAutoActive((prev) => !prev);
  }, []);

  const stopAllAuto = useCallback(() => {
    setIsBellAutoActive(false);
    setIsFishAutoActive(false);
  }, []);

  const updatePreferences = useCallback((partial: Partial<InstrumentPreferences>) => {
    setPreferences((prev) => {
      const updated = { ...prev, ...partial };
      saveInstrumentPreferences(updated);
      return updated;
    });
  }, []);

  const resetCounts = useCallback(() => {
    setBellCount(0);
    setWoodenFishCount(0);
  }, []);

  const setInitialCounts = useCallback((bell: number, fish: number) => {
    setBellCount(bell);
    setWoodenFishCount(fish);
  }, []);

  return {
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
  };
}
