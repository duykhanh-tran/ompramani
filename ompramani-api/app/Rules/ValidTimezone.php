<?php

namespace App\Rules;

use Closure;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTimezone implements ValidationRule
{
    /**
     * Map of legacy or regional aliases to canonical calculation timezones.
     *
     * @var array<string, string>
     */
    public const ALIAS_MAP = [
        'Asia/Saigon' => 'Asia/Ho_Chi_Minh',
    ];

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

        // Fast path 1: Known regional alias map
        if (isset(self::ALIAS_MAP[$trimmed])) {
            return true;
        }

        // Fast path 2: Standard common timezones
        $commonAliases = [
            'Asia/Ho_Chi_Minh',
            'UTC',
            'GMT',
        ];

        if (in_array($trimmed, $commonAliases, true)) {
            return true;
        }

        // Fast path 3: Standard IANA timezone list including backward-compatibility aliases
        if (in_array($trimmed, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
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

    /**
     * Resolve a canonical calculation timezone identifier supported safely across all PHP/OS runtimes.
     */
    public static function canonicalize(string $timezone): string
    {
        $trimmed = trim($timezone);

        if (isset(self::ALIAS_MAP[$trimmed])) {
            return self::ALIAS_MAP[$trimmed];
        }

        return $trimmed;
    }
}
