<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UnsignedBigInt implements ValidationRule
{
    private const MAX = '18446744073709551615';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $fail('Nilai harus berupa bilangan bulat rupiah.');

            return;
        }

        $digits = (string) $value;

        if (! preg_match('/^(0|[1-9][0-9]*)$/D', $digits)) {
            $fail('Nilai harus berupa bilangan bulat rupiah tanpa pemisah.');

            return;
        }

        if (strlen($digits) > strlen(self::MAX)
            || (strlen($digits) === strlen(self::MAX) && strcmp($digits, self::MAX) > 0)) {
            $fail('Nilai melebihi batas maksimum yang didukung.');
        }
    }
}
