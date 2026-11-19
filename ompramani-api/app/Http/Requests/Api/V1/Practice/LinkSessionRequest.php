<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Practice;

use Illuminate\Foundation\Http\FormRequest;

class LinkSessionRequest extends FormRequest
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
            'practice_session_id' => ['required', 'integer', 'exists:practice_sessions,id'],
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
            'practice_session_id.required' => 'Vui lòng chọn buổi thực hành hoàn thành để liên kết.',
        ];
    }
}
