<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'dharma_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'preferred_tradition_id' => ['nullable', 'integer', 'exists:traditions,id'],
            'location' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'dharma_journey_started_at' => ['nullable', 'date', 'before_or_equal:today'],
            'timezone' => ['nullable', 'string', 'timezone:all_with_bc'],
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
            'name.required' => 'Họ và tên không được để trống.',
            'dharma_name.max' => 'Pháp danh không được vượt quá 100 ký tự.',
            'date_of_birth.date' => 'Ngày sinh không đúng định dạng ngày tháng.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không được ở tương lai.',
            'date_of_birth.after' => 'Ngày sinh không hợp lệ.',
            'gender.in' => 'Giới tính được chọn không hợp lệ.',
            'preferred_tradition_id.exists' => 'Truyền thống tu học được chọn không tồn tại trong hệ thống.',
            'location.max' => 'Địa phương không được vượt quá 150 ký tự.',
            'bio.max' => 'Tiểu sử/nguyện tu không được vượt quá 1000 ký tự.',
            'dharma_journey_started_at.date' => 'Ngày bắt đầu tu học không đúng định dạng ngày tháng.',
            'dharma_journey_started_at.before_or_equal' => 'Ngày bắt đầu tu học không được ở tương lai.',
            'timezone.timezone' => 'Múi giờ không hợp lệ theo chuẩn IANA (ví dụ: Asia/Ho_Chi_Minh).',
        ];
    }
}
