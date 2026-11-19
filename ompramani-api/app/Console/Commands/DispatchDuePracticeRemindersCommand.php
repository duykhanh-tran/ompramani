<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Practice\PracticeReminderDispatchService;
use Illuminate\Console\Command;

class DispatchDuePracticeRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'practice-reminders:dispatch-due {--limit=100 : Maximum number of due reminders to process in this run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch due personal practice reminders to in-app notifications';

    /**
     * Execute the console command.
     */
    public function handle(PracticeReminderDispatchService $dispatchService): int
    {
        $limit = max((int) $this->option('limit'), 1);

        $this->info('Đang kiểm tra và xử lý các lời nhắc tu tập đến hạn...');

        $stats = $dispatchService->dispatchDueReminders($limit);

        $this->info(sprintf(
            'Hoàn tất: Tổng đến hạn: %d, Đã gửi: %d, Bỏ qua (Đã tắt): %d, Bỏ qua (Giờ yên tĩnh): %d, Bỏ qua (Quá hạn): %d, Lỗi: %d',
            $stats['total_due'] ?? 0,
            $stats['sent'] ?? 0,
            $stats['skipped_disabled'] ?? 0,
            $stats['skipped_quiet_hours'] ?? 0,
            $stats['skipped_late'] ?? 0,
            $stats['failed'] ?? 0
        ));

        return self::SUCCESS;
    }
}
