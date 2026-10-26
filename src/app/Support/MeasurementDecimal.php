<?php

namespace App\Support;

use InvalidArgumentException;

/** Decimal strings compatible with DECIMAL(15,4); measurements are never rounded. */
final class MeasurementDecimal
{
    public static function normalize(mixed $value, int $decimals = 4): string
    {
        if (! is_scalar($value) || is_bool($value)) {
            throw new InvalidArgumentException('Debe ser un número decimal.');
        }
        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/D', trim((string) $value), $parts)) {
            throw new InvalidArgumentException('Usa punto decimal, sin separadores de miles ni exponentes.');
        }
        $integer = ltrim($parts[2], '0') ?: '0';
        $fraction = $parts[3] ?? '';
        if (strlen($integer) > 11 || strlen($fraction) > 4 || strlen(rtrim($fraction, '0')) > min(4, max(0, $decimals))) {
            throw new InvalidArgumentException("Admite hasta 11 dígitos enteros y {$decimals} decimales (máximo 4).");
        }
        $fraction = rtrim($fraction, '0');
        $sign = $parts[1] === '-' && ($integer !== '0' || $fraction !== '') ? '-' : '';

        return $sign.$integer.($fraction !== '' ? '.'.$fraction : '');
    }

    public static function compare(string $left, string $right): int
    {
        $left = self::normalize($left);
        $right = self::normalize($right);
        $negativeLeft = str_starts_with($left, '-');
        $negativeRight = str_starts_with($right, '-');
        if ($negativeLeft !== $negativeRight) {
            return $negativeLeft ? -1 : 1;
        }
        $digits = static function (string $value): string {
            [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-'), 2), 2, '');

            return str_pad($whole, 11, '0', STR_PAD_LEFT).str_pad($fraction, 4, '0');
        };
        $comparison = strcmp($digits($left), $digits($right)) <=> 0;

        return $negativeLeft ? -$comparison : $comparison;
    }

    public static function format(mixed $value, int $decimals = 4): string
    {
        [$whole, $fraction] = array_pad(explode('.', self::normalize($value), 2), 2, '');
        // Preserve stored precision if the variable's configuration was reduced later.
        $decimals = max(strlen($fraction), min(4, max(0, $decimals)));

        return $whole.($decimals ? '.'.str_pad($fraction, $decimals, '0') : '');
    }
}
