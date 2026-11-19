<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchSuggestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
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
            'q.required' => 'Từ khóa tìm kiếm không được để trống.',
            'q.min' => 'Từ khóa tìm kiếm phải có ít nhất 2 ký tự.',
            'q.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
        ];
    }
}
