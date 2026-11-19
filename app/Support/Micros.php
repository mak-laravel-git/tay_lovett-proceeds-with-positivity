<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money held as integer micros (millionths of a dollar) so no float arithmetic ever touches revenue.
 */
final class Micros
{
    public const PER_UNIT = 1_000_000;

    /**
     * Parse a provider decimal ("1.32", 4, 0.5) into integer micros, rounding half up at the sixth decimal.
     *
     * @throws InvalidArgumentException when the value is not a plain non-negative decimal
     */
    public static function fromDecimal(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Floats are rendered to a fixed number of decimals first, so 0.1 + 0.2 style noise cannot leak in.
        $text = is_float($value) ? number_format($value, 7, '.', '') : trim((string) $value);

        if (preg_match('/^(\d+)(?:\.(\d+))?$/', $text, $parts) !== 1) {
            throw new InvalidArgumentException("Not a valid decimal amount: [{$text}].");
        }

        $whole = (int) $parts[1];
        $fraction = $parts[2] ?? '';
        $micros = (int) str_pad(substr($fraction, 0, 6), 6, '0');

        if (strlen($fraction) > 6 && (int) $fraction[6] >= 5) {
            $micros++;
        }

        return $whole * self::PER_UNIT + $micros;
    }

    /**
     * Human readable dollars, always two decimals ("12.34").
     */
    public static function toDollars(int $micros): string
    {
        $negative = $micros < 0;
        $cents = intdiv(abs($micros) + 5_000, 10_000);
        $text = intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$text;
    }

    /**
     * Truncate micros down to whole USD cents (no rounding up of sub-cent residue).
     */
    public static function toCentsFloor(int $micros): int
    {
        if ($micros < 0) {
            return -intdiv(abs($micros), 10_000);
        }

        return intdiv($micros, 10_000);
    }

    /**
     * PROPOSED sandbox Option B rule: exact one-third of eligible cents, floored to the cent.
     * Platform keeps the remainder. Not a live contractual formula until client confirms.
     */
    public static function oneThirdUserShareCents(int $eligibleMicros): int
    {
        if ($eligibleMicros <= 0) {
            return 0;
        }

        return intdiv(self::toCentsFloor($eligibleMicros), 3);
    }
}
