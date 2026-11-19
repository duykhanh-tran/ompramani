<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Calendar;

use App\Enums\CalendarEventType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CalendarEventsQueryRequest extends FormRequest
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
            'from' => ['required', 'string', 'date_format:Y-m-d'],
            'to' => ['required', 'string', 'date_format:Y-m-d', 'after_or_equal:from'],
            'tradition' => ['nullable', 'string', 'max:100'],
            'event_type' => ['nullable', 'string', Rule::enum(CalendarEventType::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = $this->input('from');
            $to = $this->input('to');

            if ($from && $to) {
                try {
                    $fromDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $from);
                    $toDate = CarbonImmutable::createFromFormat('Y-m-d', (string) $to);

                    if ($fromDate && $toDate && $fromDate->diffInDays($toDate) > 366) {
                        $validator->errors()->add('to', 'Khoảng thời gian tra cứu không được vượt quá 366 ngày (1 năm).');
                    }
                } catch (\Throwable) {
                    // Handled by date_format rule
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.required' => 'Vui lòng cung cấp ngày bắt đầu (from).',
            'from.date_format' => 'Ngày bắt đầu phải theo định dạng YYYY-MM-DD.',
            'to.required' => 'Vui lòng cung cấp ngày kết thúc (to).',
            'to.date_format' => 'Ngày kết thúc phải theo định dạng YYYY-MM-DD.',
            'to.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
            'event_type.Illuminate\Validation\Rules\Enum' => 'Loại sự kiện không hợp lệ.',
        ];
    }
}
