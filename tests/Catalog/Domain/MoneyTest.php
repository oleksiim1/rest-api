<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductData;
use App\Catalog\Domain\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testItHoldsAmountAndCurrency(): void
    {
        $money = new Money(2500, 'UAH');

        self::assertSame(2500, $money->amount());
        self::assertSame('UAH', $money->currency());
    }

    public function testZeroIsAllowed(): void
    {
        self::assertSame(0, (new Money(0, 'EUR'))->amount());
    }

    public function testItRejectsANegativeAmount(): void
    {
        $this->expectException(InvalidProductData::class);

        new Money(-1, 'UAH');
    }

    public function testTheLargestStorableAmountIsAllowed(): void
    {
        self::assertSame(2147483647, (new Money(Money::MAX_AMOUNT, 'UAH'))->amount());
    }

    public function testItRejectsAnAmountTheDatabaseCannotStore(): void
    {
        $this->expectException(InvalidProductData::class);

        new Money(Money::MAX_AMOUNT + 1, 'UAH');
    }

    #[DataProvider('invalidCurrencies')]
    public function testItRejectsInvalidCurrencies(string $currency): void
    {
        $this->expectException(InvalidProductData::class);

        new Money(100, $currency);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCurrencies(): iterable
    {
        yield 'empty' => [''];
        yield 'lowercase' => ['uah'];
        yield 'too short' => ['UA'];
        yield 'too long' => ['UAHH'];
        yield 'digits' => ['U4H'];
    }
}
