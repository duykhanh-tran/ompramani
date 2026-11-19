<?php

namespace App\Http\Requests\Api\V1\Practice;

use App\Rules\ValidTimezone;
use Illuminate\Foundation\Http\FormRequest;

class PracticeStreakQueryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'in:7,30,90'],
            'timezone' => ['nullable', 'string', new ValidTimezone],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.in' => 'Số ngày hiển thị lịch không hợp lệ. Chỉ chấp nhận: 7, 30, 90.',
            'days.integer' => 'Số ngày hiển thị lịch phải là số nguyên.',
            'timezone.timezone' => 'Múi giờ không hợp lệ theo chuẩn IANA (ví dụ: Asia/Ho_Chi_Minh).',
        ];
    }
}
