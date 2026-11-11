<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Recipes\Domain\Quantity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    #[DataProvider('validQuantities')]
    public function testItAcceptsPositiveNumbersWithUpToThreeDecimals(int|float $number, string $value, float $float): void
    {
        $quantity = Quantity::fromNumber($number);

        self::assertSame($value, $quantity->value());
        self::assertSame($float, $quantity->toFloat());
    }

    /** @return iterable<string, array{int|float, string, float}> */
    public static function validQuantities(): iterable
    {
        yield 'integer' => [2, '2.000', 2.0];
        yield 'half' => [0.5, '0.500', 0.5];
        yield 'one decimal' => [0.1, '0.100', 0.1];
        yield 'three decimals' => [1.125, '1.125', 1.125];
        yield 'smallest' => [0.001, '0.001', 0.001];
        yield 'largest' => [9999999.999, '9999999.999', 9999999.999];
    }

    #[DataProvider('invalidQuantities')]
    public function testItRejectsInvalidQuantities(int|float $number, string $message): void
    {
        $this->expectException(InvalidRecipeData::class);
        $this->expectExceptionMessage($message);

        Quantity::fromNumber($number);
    }

    /** @return iterable<string, array{int|float, string}> */
    public static function invalidQuantities(): iterable
    {
        yield 'zero' => [0, 'Ingredient quantity must be positive.'];
        yield 'zero float' => [0.0, 'Ingredient quantity must be positive.'];
        yield 'negative' => [-1, 'Ingredient quantity must be positive.'];
        yield 'too small' => [0.0001, 'Ingredient quantity must have at most 3 decimal places.'];
        yield 'four decimals' => [1.1255, 'Ingredient quantity must have at most 3 decimal places.'];
        yield 'float noise' => [0.1 + 0.2, 'Ingredient quantity must have at most 3 decimal places.'];
        yield 'too large' => [10000000, 'Ingredient quantity must be at most 9999999.999.'];
        yield 'infinite' => [\INF, 'Ingredient quantity must be a finite number.'];
        yield 'not a number' => [\NAN, 'Ingredient quantity must be a finite number.'];
    }
}
