<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CalendarDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.regex' => 'Định dạng ngày không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD.',
        ];
    }
}
