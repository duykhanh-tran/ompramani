<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Prayer;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPrayerIntentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'consent_public_sharing' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent_public_sharing.required' => 'Bạn cần xác nhận đồng ý với lưu ý quyền riêng tư.',
            'consent_public_sharing.accepted' => 'Bạn cần xác nhận đồng ý với lưu ý quyền riêng tư trước khi chia sẻ cộng đồng.',
        ];
    }
}
