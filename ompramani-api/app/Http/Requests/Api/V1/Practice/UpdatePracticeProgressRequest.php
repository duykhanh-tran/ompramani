<?php

namespace App\Http\Requests\Api\V1\Practice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticeProgressRequest extends FormRequest
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
            'progress_data' => ['required', 'array'],
            'sequence' => ['nullable', 'integer', 'min:1'],
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
            'progress_data.required' => 'Dữ liệu tiến trình không được để trống.',
            'progress_data.array' => 'Dữ liệu tiến trình phải là một đối tượng dữ liệu hợp lệ.',
            'sequence.integer' => 'Chỉ số sequence phải là số nguyên dương.',
        ];
    }
}
