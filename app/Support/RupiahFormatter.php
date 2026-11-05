<?php

namespace App\Support;

class RupiahFormatter
{
    public static function format(string|int $amount): string
    {
        $digits = ltrim((string) $amount, '0') ?: '0';
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits);

        return 'Rp '.($grouped ?? $digits);
    }
}
