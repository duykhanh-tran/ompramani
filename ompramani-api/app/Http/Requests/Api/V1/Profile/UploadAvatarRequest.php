<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
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
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
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
            'avatar.required' => 'Vui lòng chọn tệp hình ảnh để tải lên.',
            'avatar.image' => 'Tệp tải lên phải là một hình ảnh hợp lệ.',
            'avatar.mimes' => 'Hình ảnh chỉ chấp nhận các định dạng: jpeg, jpg, png, webp.',
            'avatar.max' => 'Dung lượng hình ảnh không được vượt quá 5 MB.',
        ];
    }
}
