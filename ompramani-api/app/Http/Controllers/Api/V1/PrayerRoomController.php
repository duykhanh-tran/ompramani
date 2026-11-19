<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Prayer\PrayerRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrayerRoomController extends Controller
{
    public function __construct(
        protected PrayerRoomService $prayerRoomService
    ) {}

    /**
     * Get public community prayer room messages.
     * Accessible by guests and authenticated users.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'before_id' => ['nullable', 'string', 'max:50'],
            'after_id' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 30;
        $viewer = $request->user('sanctum');

        $result = $this->prayerRoomService->getMessages(
            beforeId: $validated['before_id'] ?? null,
            afterId: $validated['after_id'] ?? null,
            limit: $limit,
            viewer: $viewer
        );

        return response()->json([
            'data' => $result['messages'],
            'meta' => [
                'has_more_older' => $result['has_more_older'],
                'has_more_newer' => $result['has_more_newer'],
                'oldest_id' => $result['oldest_id'],
                'newest_id' => $result['newest_id'],
            ],
        ]);
    }

    /**
     * Submit a new message or reply to the prayer room.
     * Requires authentication.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000'],
            'consent_public_sharing' => ['required', 'boolean'],
            'reply_to_public_id' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $message = $this->prayerRoomService->sendMessage(
            user: $user,
            body: (string) $validated['body'],
            consentGiven: (bool) $validated['consent_public_sharing'],
            replyToPublicId: $validated['reply_to_public_id'] ?? null
        );

        return response()->json([
            'data' => $message,
            'message' => 'Lời nguyện của bạn đã được gửi thành công và đang chờ duyệt hiển thị cộng đồng.',
        ], 201);
    }

    /**
     * Delete / withdraw own message or by moderator.
     */
    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();

        $this->prayerRoomService->deleteMessage($user, $publicId);

        return response()->json([
            'success' => true,
            'message' => 'Tin nhắn đã được thu hồi / xóa thành công.',
        ]);
    }

    /**
     * Report a message for moderation.
     */
    public function report(Request $request, string $publicId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,harassment,personal_information,fraud_or_solicitation,misinformation,unsafe_content,other'],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $result = $this->prayerRoomService->reportMessage(
            reporter: $user,
            publicId: $publicId,
            reason: (string) $validated['reason'],
            details: $validated['details'] ?? null
        );

        return response()->json($result, 201);
    }
}
