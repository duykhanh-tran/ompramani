<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\TempleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TempleSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'type' => ['nullable', 'string', Rule::in(array_column(TempleType::cases(), 'value'))],
            'place_type' => ['nullable', 'string', Rule::in(array_column(TempleType::cases(), 'value'))],
            'country' => ['nullable', 'string', 'max:5'],
            'province' => ['nullable', 'string', 'max:100'],
            'tradition' => ['nullable'],
            'sort' => ['nullable', 'string', Rule::in(['relevance', 'name_asc', 'name_desc', 'newest'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.min' => 'Từ khóa tìm kiếm phải có tối thiểu 2 ký tự.',
            'q.max' => 'Từ khóa tìm kiếm không được vượt quá 100 ký tự.',
            'type.in' => 'Loại hình cơ sở Phật giáo không hợp lệ.',
            'place_type.in' => 'Loại hình cơ sở Phật giáo không hợp lệ.',
            'sort.in' => 'Kiểu sắp xếp không được hỗ trợ.',
            'per_page.max' => 'Số lượng kết quả trên mỗi trang không được vượt quá 50.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('q') && is_string($this->input('q'))) {
            $trimmed = trim($this->input('q'));
            $this->merge([
                'q' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }
}
