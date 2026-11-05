<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UnsignedDecimal15_3 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((! is_string($value) && ! is_int($value))
            || ! preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,3})?$/D', (string) $value)) {
            $fail('Masukkan angka nonnegatif dengan maksimal 12 digit dan 3 angka desimal.');
        }
    }
}
