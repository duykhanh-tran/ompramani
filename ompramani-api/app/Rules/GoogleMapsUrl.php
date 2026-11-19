<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleMapsUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('Liên kết Google Maps không hợp lệ.');
            return;
        }

        if (! static::isValid($value)) {
            $fail('Liên kết Google Maps không hợp lệ. Vui lòng sử dụng liên kết chia sẻ chính thức từ Google Maps qua giao thức HTTPS (ví dụ: https://maps.app.goo.gl/... hoặc https://www.google.com/maps/...).');
        }
    }

    /**
     * Verify whether a given URL is a safe and supported Google Maps URL.
     */
    public static function isValid(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        // Must be HTTPS only
        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }

        // Disallow userinfo / credentials
        if (! empty($parts['user']) || ! empty($parts['pass'])) {
            return false;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';

        // 1. Mobile app share short link: maps.app.goo.gl
        if ($host === 'maps.app.goo.gl') {
            return true;
        }

        // 2. Legacy goo.gl share link: goo.gl/maps/...
        if ($host === 'goo.gl' && str_starts_with($path, '/maps')) {
            return true;
        }

        // 3. Official Google domains (e.g. google.com, google.com.vn, maps.google.com, etc.)
        $isGoogleHost = $host === 'google.com'
            || $host === 'google.com.vn'
            || str_ends_with($host, '.google.com')
            || str_ends_with($host, '.google.com.vn');

        if (! $isGoogleHost) {
            return false;
        }

        // Subdomains dedicated to maps (e.g. maps.google.com, maps.google.com.vn)
        if (str_starts_with($host, 'maps.google.')) {
            return true;
        }

        // Standard google domain with /maps path
        if (str_starts_with($path, '/maps')) {
            return true;
        }

        return false;
    }
}
