<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;

/**
 * How much of a product an ingredient needs, in that product's own unit (e.g. 0.5 of a kg).
 *
 * Kept as a decimal string with exactly 3 decimal places, the precision of the quantity
 * column, so storing and reading it back never drifts the way a float would.
 */
final class Quantity
{
    public const DECIMALS = 3;

    /**
     * Largest value the quantity column (NUMERIC(10,3)) can store.
     */
    public const MAX = 9999999.999;

    private function __construct(private readonly string $value)
    {
    }

    public static function fromNumber(int|float $number): self
    {
        if (\is_float($number) && !is_finite($number)) {
            throw new InvalidRecipeData('Ingredient quantity must be a finite number.');
        }

        if ($number <= 0) {
            throw new InvalidRecipeData('Ingredient quantity must be positive.');
        }

        if ($number > self::MAX) {
            throw new InvalidRecipeData(\sprintf('Ingredient quantity must be at most %s.', self::MAX));
        }

        $value = \sprintf('%.'.self::DECIMALS.'F', $number);

        // Rounding to 3 decimals must give back the same number, so 0.1 passes
        // (it parses to the same float as "0.100") but 1.1255 does not.
        if ((float) $value !== (float) $number) {
            throw new InvalidRecipeData(\sprintf('Ingredient quantity must have at most %d decimal places.', self::DECIMALS));
        }

        return new self($value);
    }

    /**
     * The decimal string with 3 decimal places, e.g. "0.500".
     */
    public function value(): string
    {
        return $this->value;
    }

    public function toFloat(): float
    {
        return (float) $this->value;
    }
}
