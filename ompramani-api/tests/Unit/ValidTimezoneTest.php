<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\ValidTimezone;
use PHPUnit\Framework\TestCase;

class ValidTimezoneTest extends TestCase
{
    public function test_accepts_valid_common_and_regional_timezones(): void
    {
        $this->assertTrue(ValidTimezone::isValid('Asia/Saigon'));
        $this->assertTrue(ValidTimezone::isValid('Asia/Ho_Chi_Minh'));
        $this->assertTrue(ValidTimezone::isValid('UTC'));
        $this->assertTrue(ValidTimezone::isValid('GMT'));
        $this->assertTrue(ValidTimezone::isValid('America/New_York'));
        $this->assertTrue(ValidTimezone::isValid('Europe/London'));
        $this->assertTrue(ValidTimezone::isValid('Asia/Tokyo'));
    }

    public function test_rejects_invalid_timezones(): void
    {
        $this->assertFalse(ValidTimezone::isValid('Invalid/Timezone'));
        $this->assertFalse(ValidTimezone::isValid('Mars/Olympus_Mons'));
        $this->assertFalse(ValidTimezone::isValid(''));
        $this->assertFalse(ValidTimezone::isValid('   '));
        $this->assertFalse(ValidTimezone::isValid('Asia/Non_Existent_City'));
        $this->assertFalse(ValidTimezone::isValid("Asia/Saigon\0danger"));
        $this->assertFalse(ValidTimezone::isValid(str_repeat('a', 101)));
    }

    public function test_canonicalize_maps_aliases_to_canonical_runtime_timezones(): void
    {
        $this->assertSame('Asia/Ho_Chi_Minh', ValidTimezone::canonicalize('Asia/Saigon'));
        $this->assertSame('Asia/Ho_Chi_Minh', ValidTimezone::canonicalize('Asia/Ho_Chi_Minh'));
        $this->assertSame('UTC', ValidTimezone::canonicalize('UTC'));
        $this->assertSame('America/New_York', ValidTimezone::canonicalize('America/New_York'));
    }

    public function test_validation_rule_integration(): void
    {
        $rule = new ValidTimezone;
        $failed = false;
        $failCallback = function (string $message) use (&$failed) {
            $failed = true;
        };

        // Valid Asia/Saigon
        $failed = false;
        $rule->validate('timezone', 'Asia/Saigon', $failCallback);
        $this->assertFalse($failed);

        // Valid Asia/Ho_Chi_Minh
        $failed = false;
        $rule->validate('timezone', 'Asia/Ho_Chi_Minh', $failCallback);
        $this->assertFalse($failed);

        // Invalid
        $failed = false;
        $rule->validate('timezone', 'Invalid/Timezone', $failCallback);
        $this->assertTrue($failed);
    }
}
