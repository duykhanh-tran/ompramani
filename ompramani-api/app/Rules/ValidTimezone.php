<?php

namespace App\Rules;

use Closure;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTimezone implements ValidationRule
{
    public function __construct(
        protected ?string $customMessage = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = $this->customMessage ?? 'Múi giờ không hợp lệ theo chuẩn IANA (ví dụ: Asia/Ho_Chi_Minh).';

        if (! is_string($value)) {
            $fail($message);

            return;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return;
        }

        if (! self::isValid($trimmed)) {
            $fail($message);
        }
    }

    /**
     * Determine if a timezone string is valid, safe, and recognized by PHP/IANA.
     */
    public static function isValid(string $timezone): bool
    {
        $trimmed = trim($timezone);

        if ($trimmed === '' || strlen($trimmed) > 100 || str_contains($trimmed, "\0")) {
            return false;
        }

        // Fast path 1: Standard IANA timezone list including backward-compatibility aliases
        if (in_array($trimmed, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            return true;
        }

        // Fast path 2: Standard legacy / regional aliases
        $commonAliases = [
            'Asia/Saigon',
            'Asia/Ho_Chi_Minh',
            'UTC',
            'GMT',
        ];

        if (in_array($trimmed, $commonAliases, true)) {
            return true;
        }

        // Fallback: Verify through DateTimeZone instantiation
        try {
            $tz = new DateTimeZone($trimmed);
            $name = $tz->getName();

            return ! empty($name);
        } catch (\Throwable) {
            return false;
        }
    }
}
