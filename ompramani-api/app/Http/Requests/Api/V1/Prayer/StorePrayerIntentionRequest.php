<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Prayer;

use App\Enums\PrayerCategory;
use App\Enums\PrayerVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePrayerIntentionRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:20', 'max:2000'],
            'category' => ['nullable', 'string', new Enum(PrayerCategory::class)],
            'visibility' => ['nullable', 'string', new Enum(PrayerVisibility::class)],
            'consent_public_sharing' => [
                Rule::requiredIf(fn (): bool => $this->input('visibility') === PrayerVisibility::Public->value),
                'boolean',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Xin vui lòng nhập nội dung lời nguyện.',
            'body.min' => 'Nội dung lời nguyện cần tối thiểu 20 ký tự để bày tỏ tâm nguyện trọn vẹn.',
            'body.max' => 'Nội dung lời nguyện tối đa 2000 ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'category.Illuminate\Validation\Rules\Enum' => 'Chủ đề lời nguyện không hợp lệ.',
            'visibility.Illuminate\Validation\Rules\Enum' => 'Chế độ hiển thị không hợp lệ.',
            'consent_public_sharing.required' => 'Bạn cần xác nhận đồng ý với lưu ý quyền riêng tư trước khi gửi chia sẻ cộng đồng.',
        ];
    }
}
