<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Prayer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrayerResponseRequest extends FormRequest
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
            'body' => [
                'required',
                'string',
                'min:3',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && mb_strlen(trim($value)) < 3) {
                        $fail('Lời nhắn đồng hành không được chỉ chứa khoảng trắng.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Xin vui lòng nhập nội dung lời nhắn đồng hành.',
            'body.min' => 'Lời nhắn đồng hành cần có ít nhất 3 ký tự.',
            'body.max' => 'Lời nhắn đồng hành không được vượt quá 500 ký tự.',
        ];
    }
}
