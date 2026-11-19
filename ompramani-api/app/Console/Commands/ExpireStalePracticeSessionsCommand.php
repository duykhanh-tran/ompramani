<?php

namespace App\Console\Commands;

use App\Services\Practice\PracticeSessionService;
use Illuminate\Console\Command;

class ExpireStalePracticeSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice:expire-stale-sessions {--minutes=30 : Inactivity cutoff threshold in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire stale active/paused practice sessions that have received no activity or heartbeat';

    public function handle(PracticeSessionService $service): int
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes <= 0) {
            $minutes = PracticeSessionService::DEFAULT_STALE_INACTIVITY_MINUTES;
        }

        $this->info("Đang kiểm tra và hết hạn các phiên tu tập không hoạt động quá {$minutes} phút...");

        $count = $service->expireStaleSessions($minutes);

        $this->info("Đã đánh dấu hết hạn {$count} phiên tu tập không hoạt động.");

        return Command::SUCCESS;
    }
}
