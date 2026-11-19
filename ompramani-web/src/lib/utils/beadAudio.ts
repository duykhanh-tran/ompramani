/**
 * Web Audio API Synthesizer for Mala Beads (Tràng Hạt Gỗ)
 * Creates authentic wooden bead click sounds and gentle milestone bells.
 * Zero external mp3 dependencies, completely offline and responsive.
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

export function initBeadAudio(): void {
  try {
    const ctx = getAudioContext();
    if (ctx && ctx.state === "suspended") {
      ctx.resume().catch(() => {});
    }
  } catch {
    // Ignore audio initialization errors
  }
}

/**
 * Synthesizes a realistic, crisp wooden bead click (tiếng gõ tràng hạt gỗ trầm hương).
 */
export function playBeadClick(): void {
  try {
    const ctx = getAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;

    // Master gain
    const masterGain = ctx.createGain();
    masterGain.gain.setValueAtTime(0.7, now);
    masterGain.connect(ctx.destination);

    // 1. Primary transient click (body of the wood)
    const osc = ctx.createOscillator();
    const oscGain = ctx.createGain();

    osc.type = "sine";
    // Slightly randomize pitch between 850Hz and 920Hz for organic feel
    const randomFreq = 880 + (Math.random() - 0.5) * 60;
    osc.frequency.setValueAtTime(randomFreq, now);
    osc.frequency.exponentialRampToValueAtTime(randomFreq * 0.5, now + 0.04);

    oscGain.gain.setValueAtTime(0.8, now);
    oscGain.gain.exponentialRampToValueAtTime(0.001, now + 0.035);

    osc.connect(oscGain);
    oscGain.connect(masterGain);

    osc.start(now);
    osc.stop(now + 0.04);

    // 2. High-frequency hollow wood resonance click
    const clickOsc = ctx.createOscillator();
    const clickGain = ctx.createGain();

    clickOsc.type = "triangle";
    clickOsc.frequency.setValueAtTime(1450, now);
    clickOsc.frequency.exponentialRampToValueAtTime(600, now + 0.02);

    clickGain.gain.setValueAtTime(0.4, now);
    clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.02);

    clickOsc.connect(clickGain);
    clickGain.connect(masterGain);

    clickOsc.start(now);
    clickOsc.stop(now + 0.025);
  } catch (err) {
    console.warn("Bead audio click error:", err);
  }
}

/**
 * Synthesizes a soft, uplifting chime when 108 beads (1 round) is achieved.
 */
export function playRoundMilestoneBell(): void {
  try {
    const ctx = getAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;
    const masterGain = ctx.createGain();
    masterGain.connect(ctx.destination);

    // Harmonic bell chime at 528Hz (Peace/Solfeggio)
    [528, 1056, 1584].forEach((freq, idx) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = "sine";
      osc.frequency.setValueAtTime(freq, now);

      const amp = idx === 0 ? 0.4 : 0.15 / idx;
      gain.gain.setValueAtTime(0.001, now);
      gain.gain.exponentialRampToValueAtTime(amp, now + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + 2.5);

      osc.connect(gain);
      gain.connect(masterGain);

      osc.start(now);
      osc.stop(now + 2.6);
    });
  } catch (err) {
    console.warn("Milestone bell error:", err);
  }
}
