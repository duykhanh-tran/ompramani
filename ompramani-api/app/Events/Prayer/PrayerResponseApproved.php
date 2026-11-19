<?php

declare(strict_types=1);

namespace App\Events\Prayer;

use App\Models\PrayerResponse;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrayerResponseApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PrayerResponse $response,
        public User $admin
    ) {}
}
