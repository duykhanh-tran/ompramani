<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Almanac;

use Illuminate\Foundation\Http\FormRequest;

class AlmanacDayRequest extends FormRequest
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
            'date' => ['nullable', 'date_format:Y-m-d'],
            'method' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.date_format' => 'Định dạng ngày dương lịch phải là YYYY-MM-DD.',
            'method.string' => 'Phương pháp lịch phải là chuỗi ký tự hợp lệ.',
        ];
    }
}
