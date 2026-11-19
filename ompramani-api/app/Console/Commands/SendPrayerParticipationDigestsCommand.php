<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Notification\PrayerNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPrayerParticipationDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-prayer-digests {--date= : Target date in YYYY-MM-DD format (defaults to yesterday)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily prayer participation digests to opted-in users';
    public function handle(PrayerNotificationService $notificationService): int
    {
        $dateOption = $this->option('date');
        $targetDate = null;

        if ($dateOption) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $dateOption);
            } catch (\Throwable) {
                $this->error('Định dạng ngày không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD.');

                return self::FAILURE;
            }
        }

        $this->info('Bắt đầu xử lý gửi thông báo tổng hợp lượt đồng hành cầu nguyện...');

        $count = $notificationService->sendDailyParticipationDigests($targetDate);

        $this->info("Hoàn tất xử lý. Đã gửi {$count} thông báo tổng hợp thành công.");

        return self::SUCCESS;
    }
}
