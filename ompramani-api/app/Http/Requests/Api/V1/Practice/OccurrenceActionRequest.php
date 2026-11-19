<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Practice;

use Illuminate\Foundation\Http\FormRequest;

class OccurrenceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_item_id' => ['required', 'integer', 'exists:practice_plan_items,id'],
            'local_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_item_id.required' => 'Vui lòng chọn mục kế hoạch.',
            'local_date.required' => 'Vui lòng cung cấp ngày dự kiến.',
        ];
    }
}
