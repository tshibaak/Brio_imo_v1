<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function minorUnits(string|int|float $amount): int
    {
        $value = (string) $amount;
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Money must be a non-negative decimal with at most two fractional digits.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function decimal(int $minorUnits): string
    {
        if ($minorUnits < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }

        return intdiv($minorUnits, 100).'.'.str_pad((string) ($minorUnits % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function sum(iterable $amounts): int
    {
        $sum = 0;
        foreach ($amounts as $amount) {
            $sum += self::minorUnits($amount);
        }

        return $sum;
    }
}
