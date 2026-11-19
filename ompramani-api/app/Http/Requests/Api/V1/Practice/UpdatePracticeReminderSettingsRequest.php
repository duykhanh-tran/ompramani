<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Practice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePracticeReminderSettingsRequest extends FormRequest
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
            'practice_reminders_enabled' => ['required', 'boolean'],
            'quiet_hours_enabled' => ['required', 'boolean'],
            'quiet_start_local_time' => ['nullable', 'string', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'quiet_end_local_time' => ['nullable', 'string', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'quiet_hours_timezone' => ['nullable', 'string', 'timezone:all'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $quietEnabled = (bool) $this->input('quiet_hours_enabled');
            $start = $this->input('quiet_start_local_time');
            $end = $this->input('quiet_end_local_time');

            if ($quietEnabled) {
                if (! $start) {
                    $validator->errors()->add('quiet_start_local_time', 'Vui lòng cung cấp giờ bắt đầu cho khung giờ yên tĩnh.');
                }
                if (! $end) {
                    $validator->errors()->add('quiet_end_local_time', 'Vui lòng cung cấp giờ kết thúc cho khung giờ yên tĩnh.');
                }
                if ($start && $end && $start === $end) {
                    $validator->errors()->add('quiet_start_local_time', 'Thời gian bắt đầu và kết thúc giờ yên tĩnh không được trùng nhau.');
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
            'practice_reminders_enabled.required' => 'Vui lòng cung cấp trạng thái bật/tắt nhắc nhở tu tập.',
            'practice_reminders_enabled.boolean' => 'Giá trị cài đặt nhắc nhở không hợp lệ.',
            'quiet_hours_enabled.required' => 'Vui lòng cung cấp trạng thái giờ yên tĩnh.',
            'quiet_hours_enabled.boolean' => 'Giá trị cài đặt giờ yên tĩnh không hợp lệ.',
            'quiet_start_local_time.regex' => 'Giờ bắt đầu phải theo định dạng 24 giờ (HH:MM).',
            'quiet_end_local_time.regex' => 'Giờ kết thúc phải theo định dạng 24 giờ (HH:MM).',
            'quiet_hours_timezone.timezone' => 'Múi giờ yên tĩnh không hợp lệ.',
        ];
    }
}
