<?php

namespace App\Http\Requests\Api\V1\DharmaJourney;

use Illuminate\Foundation\Http\FormRequest;

class DharmaJourneyQueryRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', 'string', 'in:7d,30d,90d,all'],
            'timezone' => ['nullable', 'string', 'timezone:all_with_bc'],
        ];
    }

    /**
     * Custom validation messages in Vietnamese.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'range.in' => 'Khoảng thời gian không hợp lệ. Chỉ chấp nhận: 7d, 30d, 90d, all.',
            'timezone.timezone' => 'Múi giờ không hợp lệ theo chuẩn IANA (ví dụ: Asia/Ho_Chi_Minh).',
        ];
    }
}
