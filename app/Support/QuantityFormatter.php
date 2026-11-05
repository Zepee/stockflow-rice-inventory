<?php

namespace App\Support;

class QuantityFormatter
{
    public static function format(string|int $quantity): string
    {
        $value = (string) $quantity;
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole) ?? $whole;
        $fraction = rtrim($fraction, '0');

        return ($negative ? '-' : '').$grouped.($fraction === '' ? '' : ','.$fraction);
    }
}
