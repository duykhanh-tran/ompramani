<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
            'current_password' => ['required', 'string', 'current_password'],
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
            'email.required' => 'Địa chỉ email mới không được để trống.',
            'email.email' => 'Địa chỉ email mới không đúng định dạng.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng bởi một tài khoản khác.',
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại để xác thực.',
            'current_password.current_password' => 'Mật khẩu hiện tại không chính xác.',
        ];
    }
}
