<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Practice;

use App\Enums\PracticeReminderScheduleType;
use App\Enums\PracticeType;
use App\Rules\ValidTimezone;
use App\Services\Practice\PracticeReminderScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StorePracticeReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'practice_type' => ['required', new Enum(PracticeType::class)],
            'title' => ['nullable', 'string', 'max:100'],
            'schedule_type' => ['required', new Enum(PracticeReminderScheduleType::class)],
            'local_time' => ['required', 'string', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'timezone' => ['required', 'string', new ValidTimezone('Múi giờ không hợp lệ.')],
            'local_date' => ['required_if:schedule_type,once', 'nullable', 'date_format:Y-m-d'],
            'weekdays' => ['required_if:schedule_type,weekly', 'nullable', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $scheduleType = $this->input('schedule_type');
            $timezone = $this->input('timezone');
            $localTime = $this->input('local_time');
            $localDate = $this->input('local_date');

            if ($scheduleType === PracticeReminderScheduleType::Once->value && $localDate && $localTime && $timezone) {
                /** @var PracticeReminderScheduleService $scheduleService */
                $scheduleService = app(PracticeReminderScheduleService::class);

                if ($scheduleService->isNonexistentLocalTime($localDate, $localTime, $timezone)) {
                    $validator->errors()->add('local_time', 'Thời gian đã chọn không tồn tại vào ngày này do đồng hồ nhảy giờ (Daylight Saving Time).');

                    return;
                }

                try {
                    $dt = Carbon::createFromFormat('Y-m-d H:i', "{$localDate} {$localTime}", $timezone);
                    if ($dt && $dt->utc()->lessThanOrEqualTo(now()->utc())) {
                        $validator->errors()->add('local_date', 'Thời gian nhắc nhở một lần phải nằm trong tương lai.');
                    }
                } catch (\Throwable) {
                    $validator->errors()->add('local_date', 'Thời gian không hợp lệ.');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'practice_type.required' => 'Vui lòng chọn loại hình tu tập.',
            'practice_type.enum' => 'Loại hình tu tập không hợp lệ.',
            'schedule_type.required' => 'Vui lòng chọn loại lịch nhắc.',
            'schedule_type.enum' => 'Loại lịch nhắc không hợp lệ.',
            'local_time.required' => 'Vui lòng chọn giờ nhắc nhở.',
            'local_time.regex' => 'Giờ nhắc phải theo định dạng 24 giờ (HH:MM).',
            'timezone.required' => 'Vui lòng chọn múi giờ.',
            'timezone.timezone' => 'Múi giờ không hợp lệ.',
            'local_date.required_if' => 'Vui lòng chọn ngày nhắc cho lịch một lần.',
            'local_date.date_format' => 'Định dạng ngày phải là YYYY-MM-DD.',
            'weekdays.required_if' => 'Vui lòng chọn ít nhất một thứ trong tuần.',
            'weekdays.min' => 'Vui lòng chọn ít nhất một thứ trong tuần.',
            'weekdays.*.between' => 'Thứ trong tuần không hợp lệ (1 = Thứ Hai, 7 = Chủ Nhật).',
            'weekdays.*.distinct' => 'Không được chọn trùng thứ trong tuần.',
        ];
    }
}
