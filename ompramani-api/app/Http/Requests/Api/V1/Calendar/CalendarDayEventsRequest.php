<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Calendar;

use App\Enums\CalendarEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarDayEventsRequest extends FormRequest
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
            'date' => ['nullable', 'string', 'date_format:Y-m-d'],
            'tradition' => ['nullable', 'string', 'max:100'],
            'event_type' => ['nullable', 'string', Rule::enum(CalendarEventType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.date_format' => 'Định dạng ngày không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD.',
            'event_type.Illuminate\Validation\Rules\Enum' => 'Loại sự kiện không hợp lệ.',
        ];
    }
}
