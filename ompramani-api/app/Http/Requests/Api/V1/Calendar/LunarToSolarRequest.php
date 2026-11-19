<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class LunarToSolarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_leap_month')) {
            $val = $this->input('is_leap_month');
            if ($val === 'true' || $val === '1' || $val === 1 || $val === true) {
                $this->merge(['is_leap_month' => true]);
            } elseif ($val === 'false' || $val === '0' || $val === 0 || $val === false) {
                $this->merge(['is_leap_month' => false]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:1900,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'day' => ['required', 'integer', 'between:1,30'],
            'is_leap_month' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Vui lòng cung cấp năm âm lịch.',
            'year.between' => 'Năm âm lịch phải nằm trong khoảng từ 1900 đến 2100.',
            'month.required' => 'Vui lòng cung cấp tháng âm lịch.',
            'month.between' => 'Tháng âm lịch phải từ 1 đến 12.',
            'day.required' => 'Vui lòng cung cấp ngày âm lịch.',
            'day.between' => 'Ngày âm lịch phải từ 1 đến 30.',
            'is_leap_month.boolean' => 'Tham số is_leap_month phải là giá trị boolean (true/false hoặc 1/0).',
        ];
    }
}
