<?php

namespace App\Http\Requests\Api\V1\Practice;

use App\Enums\PracticeType;
use App\Services\Practice\PracticeTypeRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPracticeSessionRequest extends FormRequest
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
            'practice_type' => ['required', 'string', Rule::enum(PracticeType::class)],
            'client_session_key' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', Rule::in(array_keys(PracticeTypeRegistry::SUBJECT_MAP))],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'progress_data' => ['nullable', 'array'],
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
            'practice_type.required' => 'Vui lòng chọn loại hình tu tập.',
            'practice_type.Illuminate\Validation\Rules\Enum' => 'Loại hình tu tập không hợp lệ.',
            'subject_type.in' => 'Loại nội dung Phật giáo liên kết không hợp lệ.',
            'subject_id.integer' => 'Mã nội dung Phật giáo liên kết không đúng định dạng.',
        ];
    }
}
