<?php

declare(strict_types=1);

namespace App\Events\Prayer;

use App\Models\PrayerIntention;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrayerIntentionApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PrayerIntention $intention,
        public User $admin
    ) {}
}
