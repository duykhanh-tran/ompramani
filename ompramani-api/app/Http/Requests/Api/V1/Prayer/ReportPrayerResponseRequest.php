<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Prayer;

use App\Enums\PrayerResponseReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ReportPrayerResponseRequest extends FormRequest
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
            'reason' => ['required', 'string', new Enum(PrayerResponseReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Xin vui lòng chọn lý do báo cáo.',
            'reason.Illuminate\Validation\Rules\Enum' => 'Lý do báo cáo không hợp lệ.',
            'details.max' => 'Chi tiết báo cáo không được vượt quá 500 ký tự.',
        ];
    }
}
