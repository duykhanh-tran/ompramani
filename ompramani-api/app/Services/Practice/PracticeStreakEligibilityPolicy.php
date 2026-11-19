<?php

namespace App\Services\Practice;

use App\Enums\PracticeSessionStatus;
use App\Enums\PracticeType;
use App\Models\PracticeSession;

class PracticeStreakEligibilityPolicy
{
    /**
     * Determine if a practice session is eligible to count towards streak calculation.
     */
    public function isQualifyingSession(PracticeSession $session): bool
    {
        // 1. Only completed sessions qualify (no active, paused, cancelled, expired)
        $status = $session->status instanceof PracticeSessionStatus
            ? $session->status
            : PracticeSessionStatus::tryFrom((string) $session->status);

        if ($status !== PracticeSessionStatus::Completed) {
            return false;
        }

        // 2. Must have a valid completion timestamp
        if (! $session->ended_at) {
            return false;
        }

        // 3. Resolve practice type enum
        $practiceType = $session->practice_type instanceof PracticeType
            ? $session->practice_type
            : PracticeType::tryFrom((string) $session->practice_type);

        if (! $practiceType) {
            return false;
        }

        $activeDuration = max(0, (int) $session->active_duration_seconds);
        $progress = is_array($session->progress_data) ? $session->progress_data : [];

        // 4. Evaluate per practice type eligibility rule
        return match ($practiceType) {
            PracticeType::Meditation => $activeDuration > 0,

            PracticeType::Mala => isset($progress['bead_count'])
                && is_numeric($progress['bead_count'])
                && (int) $progress['bead_count'] > 0,

            PracticeType::MantraRecitation, PracticeType::BuddhaNameRecitation => isset($progress['repetition_count'])
                && is_numeric($progress['repetition_count'])
                && (int) $progress['repetition_count'] > 0,

            PracticeType::SutraReading, PracticeType::SutraRecitation => $activeDuration > 0
                || (isset($progress['completed_sections_count']) && is_numeric($progress['completed_sections_count']) && (int) $progress['completed_sections_count'] > 0),

            // Standalone or support bell & wooden fish does not independently qualify per MVP policy
            PracticeType::BellWoodenFish => false,
        };
    }
}
