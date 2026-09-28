<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            'bare 10-digit'      => ['9876543210',       '+919876543210'],
            'trunk prefix'       => ['09876543210',      '+919876543210'],
            'spaced with +91'    => ['+91 98765 43210',  '+919876543210'],
            'hyphenated'         => ['98765-43210',      '+919876543210'],
            'country code no +'  => ['919876543210',     '+919876543210'],
            'double-zero intl'   => ['00919876543210',   '+919876543210'],
            'parentheses'        => ['(+91) 9876543210', '+919876543210'],
            'excel scientific'   => ['9.19876543210E+11', '+919876543210'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_it_normalises_to_e164(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            'too short'        => ['12345'],
            'empty'            => [''],
            'letters only'     => ['not a number'],
            'too long'         => ['1234567890123456789'],
            'indian landline-like prefix' => ['1234567890'], // does not start 6-9
            'null'             => [null],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_it_rejects_unusable_numbers(?string $input): void
    {
        $this->assertNull(PhoneNumber::normalize($input));
        $this->assertFalse(PhoneNumber::isValid($input));
    }

    public function test_it_is_idempotent(): void
    {
        $once  = PhoneNumber::normalize('9876543210');
        $twice = PhoneNumber::normalize($once);

        $this->assertSame($once, $twice);
    }

    public function test_it_formats_for_display(): void
    {
        $this->assertSame('+91 98765 43210', PhoneNumber::display('+919876543210'));
        $this->assertSame('--', PhoneNumber::display(null));
    }
}
