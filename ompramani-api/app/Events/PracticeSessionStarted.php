<?php

namespace App\Events;

use App\Models\PracticeSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PracticeSessionStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PracticeSession $session
    ) {}
}
