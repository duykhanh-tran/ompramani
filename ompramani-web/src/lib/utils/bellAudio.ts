/**
 * Tibetan Singing Bowl / Buddhist Mindfulness Bell Audio Synthesizer
 * Uses Web Audio API with harmonic overtones and natural exponential decay.
 * Zero external mp3 dependencies, works 100% offline and instantly responsive.
 */

let audioCtx: AudioContext | null = null;

function getAudioContext(): AudioContext | null {
  if (typeof window === "undefined") return null;

  if (!audioCtx) {
    const AudioContextClass =
      window.AudioContext ||
      (window as unknown as { webkitAudioContext: typeof AudioContext })
        .webkitAudioContext;
    if (AudioContextClass) {
      audioCtx = new AudioContextClass();
    }
  }

  if (audioCtx && audioCtx.state === "suspended") {
    audioCtx.resume().catch(() => {});
  }

  return audioCtx;
}

/**
 * Initialize / unlock audio context on initial user click or tap.
 */
export function initAudioContext(): void {
  try {
    const ctx = getAudioContext();
    if (ctx && ctx.state === "suspended") {
      ctx.resume().catch(() => {});
    }
  } catch (err) {
    console.warn("Could not initialize AudioContext:", err);
  }
}

interface ToneHarmonic {
  ratio: number;
  gain: number;
  decay: number;
}

// Frequencies and resonance profiles matching authentic hand-hammered singing bowl
const BOWL_HARMONICS: ToneHarmonic[] = [
  { ratio: 1.0, gain: 0.55, decay: 5.0 },     // Fundamental (432Hz)
  { ratio: 2.01, gain: 0.28, decay: 4.2 },    // Second overtone (octave + slight shimmer)
  { ratio: 3.02, gain: 0.16, decay: 3.5 },    // Third overtone
  { ratio: 4.54, gain: 0.09, decay: 2.8 },    // High metallic resonance
  { ratio: 5.82, gain: 0.04, decay: 2.0 },    // Shimmer
];

/**
 * Strike a single singing bowl chime at given time offset.
 */
function strikeChime(ctx: AudioContext, startTime: number, baseFreq = 432): void {
  const masterGain = ctx.createGain();
  masterGain.connect(ctx.destination);

  BOWL_HARMONICS.forEach((harmonic, index) => {
    const osc = ctx.createOscillator();
    const gainNode = ctx.createGain();

    osc.type = index === 0 ? "sine" : "sine";
    // Slightly detune to create acoustic beating
    const detuneCents = (index % 2 === 0 ? 1 : -1) * (index * 3);
    osc.frequency.setValueAtTime(baseFreq * harmonic.ratio, startTime);
    osc.detune.setValueAtTime(detuneCents, startTime);

    // Envelope
    gainNode.gain.setValueAtTime(0.0001, startTime);
    gainNode.gain.exponentialRampToValueAtTime(harmonic.gain, startTime + 0.03);
    gainNode.gain.exponentialRampToValueAtTime(0.0001, startTime + harmonic.decay);

    osc.connect(gainNode);
    gainNode.connect(masterGain);

    osc.start(startTime);
    osc.stop(startTime + harmonic.decay + 0.1);
  });
}

/**
 * Play singing bowl chimes based on session event.
 */
export function playSingingBowl(
  type: "start" | "interval" | "end" | "bell" = "bell"
): void {
  try {
    const ctx = getAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;

    switch (type) {
      case "start":
        // 2 meditative chimes spaced by 1.2 seconds
        strikeChime(ctx, now, 432);
        strikeChime(ctx, now + 1.2, 540);
        break;

      case "end":
        // 3 celebratory chimes
        strikeChime(ctx, now, 432);
        strikeChime(ctx, now + 1.4, 540);
        strikeChime(ctx, now + 2.8, 648);
        break;

      case "interval":
      case "bell":
      default:
        strikeChime(ctx, now, 432);
        break;
    }
  } catch (err) {
    console.warn("Singing bowl audio error:", err);
  }
}
