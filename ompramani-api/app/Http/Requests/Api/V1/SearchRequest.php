<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
            'type' => ['nullable', 'string', 'in:all,sutra,feature,mantra,figure,article'],
            'tradition' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'string', 'in:relevance,newest'],
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
            'q.required' => 'Từ khóa tìm kiếm không được để trống.',
            'q.min' => 'Từ khóa tìm kiếm phải có ít nhất 2 ký tự.',
            'q.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
            'per_page.max' => 'Số lượng kết quả mỗi trang tối đa là 50.',
            'per_page.min' => 'Số lượng kết quả mỗi trang tối thiểu là 1.',
            'type.in' => 'Loại tìm kiếm không hợp lệ.',
        ];
    }
}
