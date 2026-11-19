<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class SolarToLunarRequest extends FormRequest
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
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
        ];
    }

    /**
     * Get the resolved date string in YYYY-MM-DD format.
     */
    public function resolvedDate(): string
    {
        if ($this->filled('date')) {
            return (string) $this->input('date');
        }

        if ($this->filled('year') && $this->filled('month') && $this->filled('day')) {
            return sprintf('%04d-%02d-%02d', (int) $this->input('year'), (int) $this->input('month'), (int) $this->input('day'));
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.regex' => 'Định dạng ngày dương lịch không hợp lệ. Vui lòng sử dụng YYYY-MM-DD.',
            'year.between' => 'Năm dương lịch phải nằm trong khoảng từ 1900 đến 2100.',
            'month.between' => 'Tháng dương lịch phải nằm trong khoảng từ 1 đến 12.',
            'day.between' => 'Ngày dương lịch phải nằm trong khoảng từ 1 đến 31.',
        ];
    }
}
