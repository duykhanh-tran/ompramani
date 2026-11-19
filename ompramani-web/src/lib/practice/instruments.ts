/**
 * Instrument Preferences & Constants for Bell & Wooden Fish (Chuông & Mõ)
 */

export interface InstrumentPreferences {
  masterVolume: number; // 0.0 - 1.0 (default: 0.8)
  bellVolume: number; // 0.0 - 1.0 (default: 0.85)
  woodenFishVolume: number; // 0.0 - 1.0 (default: 0.85)
  isMuted: boolean; // default: false
  bellInterval: number; // seconds between auto bell strikes (default: 2)
  woodenFishInterval: number; // seconds between auto wooden fish strikes (default: 2)
  hapticEnabled: boolean; // mobile vibration on strike (default: true)
}

export const DEFAULT_INSTRUMENT_PREFERENCES: InstrumentPreferences = {
  masterVolume: 0.8,
  bellVolume: 0.85,
  woodenFishVolume: 0.85,
  isMuted: false,
  bellInterval: 2,
  woodenFishInterval: 2,
  hapticEnabled: true,
};

const STORAGE_KEY = "ompramani_instrument_preferences";

export function loadInstrumentPreferences(): InstrumentPreferences {
  if (typeof window === "undefined") {
    return DEFAULT_INSTRUMENT_PREFERENCES;
  }

  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return DEFAULT_INSTRUMENT_PREFERENCES;
    const parsed = JSON.parse(raw);
    return {
      ...DEFAULT_INSTRUMENT_PREFERENCES,
      ...parsed,
    };
  } catch {
    return DEFAULT_INSTRUMENT_PREFERENCES;
  }
}

export function saveInstrumentPreferences(
  prefs: Partial<InstrumentPreferences>
): InstrumentPreferences {
  if (typeof window === "undefined") {
    return DEFAULT_INSTRUMENT_PREFERENCES;
  }

  try {
    const current = loadInstrumentPreferences();
    const updated = { ...current, ...prefs };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(updated));
    return updated;
  } catch {
    return DEFAULT_INSTRUMENT_PREFERENCES;
  }
}

export interface IntervalOption {
  label: string;
  seconds: number;
  bpm?: number;
}

export const BELL_INTERVAL_OPTIONS: IntervalOption[] = [
  { label: "1 giây", seconds: 1 },
  { label: "2 giây", seconds: 2 },
  { label: "3 giây", seconds: 3 },
  { label: "5 giây", seconds: 5 },
  { label: "10 giây", seconds: 10 },
  { label: "30 giây", seconds: 30 },
  { label: "1 phút", seconds: 60 },
  { label: "3 phút", seconds: 180 },
  { label: "5 phút", seconds: 300 },
];

export const WOODEN_FISH_INTERVAL_OPTIONS: IntervalOption[] = [
  { label: "0.5 giây (120 BPM)", seconds: 0.5, bpm: 120 },
  { label: "0.75 giây (80 BPM)", seconds: 0.75, bpm: 80 },
  { label: "1 giây (60 BPM)", seconds: 1, bpm: 60 },
  { label: "1.5 giây (40 BPM)", seconds: 1.5, bpm: 40 },
  { label: "2 giây (30 BPM)", seconds: 2, bpm: 30 },
  { label: "3 giây (20 BPM)", seconds: 3, bpm: 20 },
  { label: "5 giây", seconds: 5 },
];

/**
 * Format total seconds into MM:SS display.
 */
export function formatTimer(totalSeconds: number): string {
  const s = Math.max(0, Math.floor(totalSeconds || 0));
  const mins = Math.floor(s / 60);
  const secs = s % 60;
  return `${mins.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
}
