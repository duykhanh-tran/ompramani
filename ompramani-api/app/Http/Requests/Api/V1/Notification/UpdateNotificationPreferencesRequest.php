<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'moderation_updates_enabled' => ['required', 'boolean'],
            'new_response_updates_enabled' => ['required', 'boolean'],
            'participation_digest_enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'moderation_updates_enabled.required' => 'Vui lòng cung cấp trạng thái cài đặt thông báo kiểm duyệt.',
            'moderation_updates_enabled.boolean' => 'Giá trị cài đặt kiểm duyệt không hợp lệ.',
            'new_response_updates_enabled.required' => 'Vui lòng cung cấp trạng thái cài đặt thông báo lời nhắn mới.',
            'new_response_updates_enabled.boolean' => 'Giá trị cài đặt lời nhắn mới không hợp lệ.',
            'participation_digest_enabled.required' => 'Vui lòng cung cấp trạng thái cài đặt thông báo tổng hợp.',
            'participation_digest_enabled.boolean' => 'Giá trị cài đặt tổng hợp không hợp lệ.',
        ];
    }
}
