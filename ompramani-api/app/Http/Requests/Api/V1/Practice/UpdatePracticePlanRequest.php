<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Practice;

use App\Rules\ValidTimezone;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticePlanRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['sometimes', 'required', 'string', new ValidTimezone('Múi giờ không hợp lệ.')],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên kế hoạch tu tập.',
            'name.max' => 'Tên kế hoạch không được vượt quá 150 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
            'ends_on.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
        ];
    }
}
