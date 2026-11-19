<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Almanac;

use Illuminate\Foundation\Http\FormRequest;

class AlmanacMonthRequest extends FormRequest
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
            'year' => ['required', 'integer', 'between:1900,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'method' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Vui lòng cung cấp năm dương lịch.',
            'year.integer' => 'Năm dương lịch phải là số nguyên.',
            'year.between' => 'Năm dương lịch phải nằm trong khoảng từ 1900 đến 2100.',
            'month.required' => 'Vui lòng cung cấp tháng dương lịch.',
            'month.integer' => 'Tháng dương lịch phải là số nguyên.',
            'month.between' => 'Tháng dương lịch phải nằm trong khoảng từ 1 đến 12.',
            'method.string' => 'Phương pháp lịch phải là chuỗi ký tự hợp lệ.',
        ];
    }
}
