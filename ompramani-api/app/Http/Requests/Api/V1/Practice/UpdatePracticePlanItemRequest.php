<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Practice;

use App\Enums\PracticePlanScheduleType;
use App\Enums\PracticeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePracticePlanItemRequest extends FormRequest
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
            'practice_type' => ['sometimes', 'required', new Enum(PracticeType::class)],
            'title' => ['nullable', 'string', 'max:150'],
            'schedule_type' => ['sometimes', 'required', new Enum(PracticePlanScheduleType::class)],
            'local_date' => ['nullable', 'date_format:Y-m-d'],
            'local_time' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'weekdays' => ['nullable', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'target_duration_seconds' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'target_repetitions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'sync_reminder' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'practice_type.required' => 'Vui lòng chọn loại hình tu tập.',
            'schedule_type.required' => 'Vui lòng chọn tần suất thực hành.',
            'local_time.regex' => 'Giờ thực hành không hợp lệ (định dạng HH:MM).',
            'weekdays.*.between' => 'Thứ trong tuần không hợp lệ (1 = Thứ Hai ... 7 = Chủ Nhật).',
            'target_duration_seconds.min' => 'Thời lượng mục tiêu tối thiểu là 60 giây (1 phút).',
            'target_duration_seconds.max' => 'Thời lượng mục tiêu không được vượt quá 24 giờ.',
            'target_repetitions.min' => 'Số lần mục tiêu tối thiểu là 1.',
        ];
    }
}
