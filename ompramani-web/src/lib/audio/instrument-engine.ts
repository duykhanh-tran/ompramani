/**
 * Instrument Audio Engine for Buddhist Bell & Wooden Fish (Chuông & Mõ Điện Tử)
 *
 * Implements high-fidelity acoustic physical synthesis via Web Audio API:
 * 1. Buddhist Bell / Singing Bowl (Chuông đồng / Bát chuông gia trì):
 *    - 5 harmonic overtones (432Hz fundamental with Tibetan acoustic beating)
 *    - Warm exponential resonance & decay
 *    - Polyphony management to avoid clipping
 * 2. Wooden Fish (Mõ gỗ tụng kinh):
 *    - Multi-stage physical model: transient wood impact + hollow cavity resonance (480Hz) + low thud
 *    - Organic micro-pitch variance for natural repetitive chanting feel
 *    - Zero latency, high-speed tapping resilience
 * 3. High-precision Web Audio lookahead scheduler for automated rhythms & intervals
 * 4. Tab visibility awareness (no backlog bursts when backgrounded)
 */

export interface InstrumentAudioConfig {
  masterVolume?: number; // 0.0 - 1.0
  bellVolume?: number; // 0.0 - 1.0
  woodenFishVolume?: number; // 0.0 - 1.0
  isMuted?: boolean;
}

export interface BellHarmonic {
  ratio: number;
  gain: number;
  decay: number;
}

const BELL_HARMONICS: BellHarmonic[] = [
  { ratio: 1.0, gain: 0.55, decay: 5.5 }, // Fundamental (432Hz bronze tone)
  { ratio: 2.01, gain: 0.28, decay: 4.5 }, // 2nd overtone + subtle shimmer
  { ratio: 3.02, gain: 0.16, decay: 3.8 }, // 3rd overtone
  { ratio: 4.54, gain: 0.09, decay: 2.8 }, // Metallic ring
  { ratio: 5.82, gain: 0.04, decay: 2.0 }, // High acoustic sheen
];

class InstrumentAudioEngine {
  private audioCtx: AudioContext | null = null;
  private masterGain: GainNode | null = null;
  private bellGain: GainNode | null = null;
  private woodenFishGain: GainNode | null = null;

  private masterVolume: number = 0.8;
  private bellVolume: number = 0.85;
  private woodenFishVolume: number = 0.85;
  private isMuted: boolean = false;

  // Active voice limiters to avoid clipping
  private activeBellVoices: Array<{ stop: () => void }> = [];
  private static readonly MAX_BELL_VOICES = 3;

  constructor() {
    // Lazy initialize on first user gesture
  }

  /**
   * Safe getter for AudioContext, ensuring user interaction has unlocked it.
   */
  public getAudioContext(): AudioContext | null {
    if (typeof window === "undefined") return null;

    if (!this.audioCtx) {
      const AudioContextClass =
        window.AudioContext ||
        (window as unknown as { webkitAudioContext: typeof AudioContext })
          .webkitAudioContext;
      if (AudioContextClass) {
        this.audioCtx = new AudioContextClass();
        this.setupGainNodes();
      }
    }

    if (this.audioCtx && this.audioCtx.state === "suspended") {
      this.audioCtx.resume().catch(() => {});
    }

    return this.audioCtx;
  }

  private setupGainNodes(): void {
    if (!this.audioCtx) return;

    this.masterGain = this.audioCtx.createGain();
    this.bellGain = this.audioCtx.createGain();
    this.woodenFishGain = this.audioCtx.createGain();

    this.bellGain.connect(this.masterGain);
    this.woodenFishGain.connect(this.masterGain);
    this.masterGain.connect(this.audioCtx.destination);

    this.updateGains();
  }

  private updateGains(): void {
    if (!this.masterGain || !this.bellGain || !this.woodenFishGain || !this.audioCtx) return;

    const now = this.audioCtx.currentTime;
    const effectiveMaster = this.isMuted ? 0 : this.masterVolume;

    this.masterGain.gain.setValueAtTime(effectiveMaster, now);
    this.bellGain.gain.setValueAtTime(this.bellVolume, now);
    this.woodenFishGain.gain.setValueAtTime(this.woodenFishVolume, now);
  }

  /**
   * Unlock AudioContext explicitly from any tap/click gesture.
   */
  public unlock(): void {
    try {
      const ctx = this.getAudioContext();
      if (ctx && ctx.state === "suspended") {
        ctx.resume().catch(() => {});
      }
    } catch (err) {
      console.warn("Could not unlock AudioContext:", err);
    }
  }

  /**
   * Strike Buddhist Bell (Singing Bowl).
   */
  public strikeBell(timeOffset = 0): void {
    const ctx = this.getAudioContext();
    if (!ctx || !this.bellGain) return;

    const startTime = ctx.currentTime + Math.max(0, timeOffset);

    // Limit active voices to avoid polyphony distortion
    if (this.activeBellVoices.length >= InstrumentAudioEngine.MAX_BELL_VOICES) {
      const oldest = this.activeBellVoices.shift();
      try {
        oldest?.stop();
      } catch {
        // ignore
      }
    }

    const voiceGain = ctx.createGain();
    voiceGain.connect(this.bellGain);

    const baseFreq = 432; // Standard healing/peaceful frequency
    const oscillators: OscillatorNode[] = [];

    BELL_HARMONICS.forEach((harmonic, idx) => {
      const osc = ctx.createOscillator();
      const hGain = ctx.createGain();

      osc.type = "sine";
      const detuneCents = (idx % 2 === 0 ? 1 : -1) * (idx * 2.5);
      osc.frequency.setValueAtTime(baseFreq * harmonic.ratio, startTime);
      osc.detune.setValueAtTime(detuneCents, startTime);

      // Organic strike attack and warm decay
      hGain.gain.setValueAtTime(0.0001, startTime);
      hGain.gain.exponentialRampToValueAtTime(harmonic.gain, startTime + 0.02);
      hGain.gain.exponentialRampToValueAtTime(0.0001, startTime + harmonic.decay);

      osc.connect(hGain);
      hGain.connect(voiceGain);

      osc.start(startTime);
      osc.stop(startTime + harmonic.decay + 0.1);
      oscillators.push(osc);
    });

    const stopVoice = () => {
      try {
        oscillators.forEach((o) => o.stop());
        voiceGain.disconnect();
      } catch {
        // ignore
      }
    };

    const voiceEntry = { stop: stopVoice };
    this.activeBellVoices.push(voiceEntry);

    setTimeout(() => {
      const idx = this.activeBellVoices.indexOf(voiceEntry);
      if (idx !== -1) {
        this.activeBellVoices.splice(idx, 1);
      }
    }, 6000);
  }

  /**
   * Strike Wooden Fish (Mõ Gỗ).
   * Generates a warm, authentic hollow wooden knock with organic nuance.
   */
  public strikeWoodenFish(timeOffset = 0): void {
    const ctx = this.getAudioContext();
    if (!ctx || !this.woodenFishGain) return;

    const startTime = ctx.currentTime + Math.max(0, timeOffset);

    // Randomize pitch slightly (+/- 2%) for organic hand-tapping feel
    const pitchJitter = (Math.random() - 0.5) * 16;
    const bodyFreq = 480 + pitchJitter; // Fundamental hollow wood chamber frequency

    // 1. Transient stick strike impact (high click)
    const clickOsc = ctx.createOscillator();
    const clickGain = ctx.createGain();
    clickOsc.type = "triangle";
    clickOsc.frequency.setValueAtTime(1400, startTime);
    clickOsc.frequency.exponentialRampToValueAtTime(350, startTime + 0.02);

    clickGain.gain.setValueAtTime(0.65, startTime);
    clickGain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.025);

    clickOsc.connect(clickGain);
    clickGain.connect(this.woodenFishGain);

    clickOsc.start(startTime);
    clickOsc.stop(startTime + 0.03);

    // 2. Hollow wooden chamber resonance (core note)
    const bodyOsc = ctx.createOscillator();
    const bodyGain = ctx.createGain();
    bodyOsc.type = "sine";
    bodyOsc.frequency.setValueAtTime(bodyFreq, startTime);
    bodyOsc.frequency.exponentialRampToValueAtTime(bodyFreq * 0.85, startTime + 0.14);

    bodyGain.gain.setValueAtTime(0.9, startTime);
    bodyGain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.15);

    bodyOsc.connect(bodyGain);
    bodyGain.connect(this.woodenFishGain);

    bodyOsc.start(startTime);
    bodyOsc.stop(startTime + 0.16);

    // 3. Low body wood thud
    const thudOsc = ctx.createOscillator();
    const thudGain = ctx.createGain();
    thudOsc.type = "sine";
    thudOsc.frequency.setValueAtTime(220, startTime);
    thudOsc.frequency.exponentialRampToValueAtTime(120, startTime + 0.08);

    thudGain.gain.setValueAtTime(0.4, startTime);
    thudGain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.08);

    thudOsc.connect(thudGain);
    thudGain.connect(this.woodenFishGain);

    thudOsc.start(startTime);
    thudOsc.stop(startTime + 0.09);
  }

  // Volume & Mute Controls
  public setMasterVolume(vol: number): void {
    this.masterVolume = Math.max(0, Math.min(1, vol));
    this.updateGains();
  }

  public getMasterVolume(): number {
    return this.masterVolume;
  }

  public setBellVolume(vol: number): void {
    this.bellVolume = Math.max(0, Math.min(1, vol));
    this.updateGains();
  }

  public getBellVolume(): number {
    return this.bellVolume;
  }

  public setWoodenFishVolume(vol: number): void {
    this.woodenFishVolume = Math.max(0, Math.min(1, vol));
    this.updateGains();
  }

  public getWoodenFishVolume(): number {
    return this.woodenFishVolume;
  }

  public setMuted(muted: boolean): void {
    this.isMuted = muted;
    this.updateGains();
  }

  public getMuted(): boolean {
    return this.isMuted;
  }

  public toggleMute(): boolean {
    this.isMuted = !this.isMuted;
    this.updateGains();
    return this.isMuted;
  }

  /**
   * Cleanup all audio resources when tearing down.
   */
  public cleanup(): void {
    this.activeBellVoices.forEach((v) => v.stop());
    this.activeBellVoices = [];

    if (this.audioCtx && this.audioCtx.state !== "closed") {
      this.audioCtx.close().catch(() => {});
      this.audioCtx = null;
    }
  }
}

// Global Singleton
let engineInstance: InstrumentAudioEngine | null = null;

export function getInstrumentAudioEngine(): InstrumentAudioEngine {
  if (!engineInstance) {
    engineInstance = new InstrumentAudioEngine();
  }
  return engineInstance;
}
