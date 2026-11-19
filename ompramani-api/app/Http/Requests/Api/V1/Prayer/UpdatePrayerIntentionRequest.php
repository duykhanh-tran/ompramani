<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Prayer;

use App\Enums\PrayerCategory;
use App\Enums\PrayerVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePrayerIntentionRequest extends FormRequest
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
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string', 'min:20', 'max:2000'],
            'category' => ['sometimes', 'nullable', 'string', new Enum(PrayerCategory::class)],
            'visibility' => ['sometimes', 'string', new Enum(PrayerVisibility::class)],
            'consent_public_sharing' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Xin vui lòng nhập nội dung lời nguyện.',
            'body.min' => 'Nội dung lời nguyện cần tối thiểu 20 ký tự.',
            'body.max' => 'Nội dung lời nguyện tối đa 2000 ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'category.Illuminate\Validation\Rules\Enum' => 'Chủ đề lời nguyện không hợp lệ.',
            'visibility.Illuminate\Validation\Rules\Enum' => 'Chế độ hiển thị không hợp lệ.',
        ];
    }
}
