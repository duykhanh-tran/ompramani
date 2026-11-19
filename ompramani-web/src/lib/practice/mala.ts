/**
 * Digital Mala 108 (Tràng Hạt Điện Tử 108 Hạt)
 * Pure, distraction-free counting utility.
 */

export const BEADS_PER_ROUND = 108;

export interface MalaRoundStats {
  beadCount: number; // 0 to 108
  isCompletedRound: boolean;
  displayCounter: string; // e.g. "0 / 108"
}

/**
 * Pure calculation function for Mala counting.
 * Enforces:
 * - Minimum: 0
 * - Maximum: 108
 * - 108 beads = 1 round
 */
export function calculateMalaRounds(beadCount: number): MalaRoundStats {
  const safeCount = Math.max(0, Math.min(BEADS_PER_ROUND, Math.floor(beadCount || 0)));
  return {
    beadCount: safeCount,
    isCompletedRound: safeCount >= BEADS_PER_ROUND,
    displayCounter: `${safeCount} / ${BEADS_PER_ROUND}`,
  };
}
