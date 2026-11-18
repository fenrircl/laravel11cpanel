<?php

namespace App\Support;

final class LocalizedDecimal
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (string) $value : null;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim(str_replace(["\u{00A0}", ' '], '', $value));
        $value = preg_replace('/[^0-9,.+\-]/u', '', $value) ?? '';
        if ($value === '' || !preg_match('/^[+-]?[0-9.,]+$/', $value)) {
            return null;
        }

        if (str_contains($value, ',')) {
            $lastComma = strrpos($value, ',');
            $integerPart = str_replace([',', '.'], '', substr($value, 0, $lastComma));
            $fractionPart = str_replace([',', '.'], '', substr($value, $lastComma + 1));
            $sign = str_starts_with($integerPart, '-') || str_starts_with($integerPart, '+')
                ? substr($integerPart, 0, 1)
                : '';
            $integerPart = ltrim($integerPart, '+-');
            $value = $sign . ($integerPart === '' ? '0' : $integerPart)
                . ($fractionPart !== '' ? '.' . $fractionPart : '');
        } elseif (preg_match('/^[+-]?\d{1,3}(?:\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? $value : null;
    }
}
