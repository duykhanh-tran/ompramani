<?php

declare(strict_types=1);

namespace App\Events\Prayer;

use App\Models\PrayerIntention;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrayerIntentionRejected implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PrayerIntention $intention,
        public User $admin,
        public string $reasonCode,
        public ?string $userNote = null
    ) {}
}
