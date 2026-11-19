<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerNotificationType: string
{
    case PrayerIntentionApproved = 'prayer_intention_approved';
    case PrayerIntentionRejected = 'prayer_intention_rejected';
    case PrayerIntentionRevoked = 'prayer_intention_revoked';

    case PrayerResponseApproved = 'prayer_response_approved';
    case PrayerResponseRejected = 'prayer_response_rejected';
    case PrayerResponseRevoked = 'prayer_response_revoked';

    case PrayerNewApprovedResponse = 'prayer_new_approved_response';
    case PrayerParticipationDigest = 'prayer_participation_digest';
    case PersonalPracticeReminder = 'personal_practice_reminder';

    public function label(): string
    {
        return match ($this) {
            self::PrayerIntentionApproved => 'Lời nguyện đã được duyệt',
            self::PrayerIntentionRejected => 'Lời nguyện chưa được duyệt',
            self::PrayerIntentionRevoked => 'Lời nguyện đã ngừng hiển thị',
            self::PrayerResponseApproved => 'Lời nhắn đồng hành đã được duyệt',
            self::PrayerResponseRejected => 'Lời nhắn đồng hành chưa được duyệt',
            self::PrayerResponseRevoked => 'Lời nhắn đồng hành đã ngừng hiển thị',
            self::PrayerNewApprovedResponse => 'Lời nhắn mới dưới lời nguyện',
            self::PrayerParticipationDigest => 'Tổng hợp hoạt động đồng hành',
            self::PersonalPracticeReminder => 'Nhắc nhở tu tập cá nhân',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::PrayerIntentionApproved,
            self::PrayerIntentionRejected,
            self::PrayerIntentionRevoked,
            self::PrayerResponseApproved,
            self::PrayerResponseRejected,
            self::PrayerResponseRevoked => 'moderation',

            self::PrayerNewApprovedResponse,
            self::PrayerParticipationDigest => 'community',

            self::PersonalPracticeReminder => 'practice_reminder',
        };
    }

    /**
     * Build privacy-safe default title, message, and destination.
     *
     * @param  array<string, mixed>  $metadata
     * @return array{title: string, message: string, destination: string}
     */
    public function defaultTemplate(array $metadata = []): array
    {
        $publicId = $metadata['resource_public_id'] ?? '';
        $parentPublicId = $metadata['parent_public_id'] ?? $publicId;

        return match ($this) {
            self::PrayerIntentionApproved => [
                'title' => 'Lời nguyện đã được duyệt',
                'message' => 'Lời nguyện của bạn đã được duyệt để chia sẻ cùng cộng đồng.',
                'destination' => '/tai-khoan/loi-nguyen',
            ],
            self::PrayerIntentionRejected => [
                'title' => 'Lời nguyện chưa được duyệt',
                'message' => 'Lời nguyện của bạn chưa được chấp thuận để chia sẻ. Vui lòng kiểm tra lý do và chỉnh sửa.',
                'destination' => '/tai-khoan/loi-nguyen',
            ],
            self::PrayerIntentionRevoked => [
                'title' => 'Lời nguyện đã ngừng hiển thị',
                'message' => 'Lời nguyện đã chia sẻ của bạn đã được ban quản trị ngừng hiển thị công khai.',
                'destination' => '/tai-khoan/loi-nguyen',
            ],
            self::PrayerResponseApproved => [
                'title' => 'Lời nhắn đã được duyệt',
                'message' => 'Lời nhắn đồng hành của bạn đã được duyệt và hiển thị công khai.',
                'destination' => '/tai-khoan/loi-nhan-dong-hanh',
            ],
            self::PrayerResponseRejected => [
                'title' => 'Lời nhắn chưa được duyệt',
                'message' => 'Lời nhắn đồng hành của bạn chưa được chấp thuận để hiển thị.',
                'destination' => '/tai-khoan/loi-nhan-dong-hanh',
            ],
            self::PrayerResponseRevoked => [
                'title' => 'Lời nhắn đã ngừng hiển thị',
                'message' => 'Lời nhắn đồng hành của bạn đã được ban quản trị ngừng hiển thị công khai.',
                'destination' => '/tai-khoan/loi-nhan-dong-hanh',
            ],
            self::PrayerNewApprovedResponse => [
                'title' => 'Lời nhắn mới dưới lời nguyện',
                'message' => 'Một lời nhắn đồng hành mới vừa được duyệt dưới lời nguyện bạn chia sẻ.',
                'destination' => $parentPublicId ? "/cau-nguyen/{$parentPublicId}" : '/tai-khoan/loi-nguyen',
            ],
            self::PrayerParticipationDigest => [
                'title' => 'Hoạt động đồng hành mới',
                'message' => isset($metadata['count']) && (int) $metadata['count'] > 0
                    ? 'Trong ngày qua, có thêm '.((int) $metadata['count']).' lượt đồng hành cùng lời nguyện bạn chia sẻ.'
                    : 'Trong ngày qua, có thêm hoạt động đồng hành cùng lời nguyện bạn chia sẻ.',
                'destination' => '/tai-khoan/loi-nguyen',
            ],
            self::PersonalPracticeReminder => [
                'title' => $metadata['title'] ?? 'Thời gian tu tập',
                'message' => $metadata['message'] ?? 'Đã đến thời gian thực hành tu tập bạn đã lựa chọn.',
                'destination' => $metadata['destination'] ?? '/tai-khoan/nhac-nho-tu-tap',
            ],
        };
    }
}
