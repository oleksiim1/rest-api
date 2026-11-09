<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\Unit;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testItExposesItsState(): void
    {
        $id = ProductId::fromString('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b');
        $product = new Product($id, new ProductName('Potato'), new Money(2500, 'UAH'), Unit::Kilogram);

        self::assertTrue($product->id()->equals($id));
        self::assertSame('Potato', $product->name()->value());
        self::assertSame(2500, $product->price()->amount());
        self::assertSame(Unit::Kilogram, $product->unit());
    }

    public function testUpdateReplacesNamePriceAndUnit(): void
    {
        $product = new Product(
            ProductId::fromString('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b'),
            new ProductName('Eggs'),
            new Money(7000, 'UAH'),
            Unit::Dozen,
        );

        $product->update(new ProductName('Quail eggs'), new Money(450, 'UAH'), Unit::Piece);

        self::assertSame('Quail eggs', $product->name()->value());
        self::assertSame(450, $product->price()->amount());
        self::assertSame(Unit::Piece, $product->unit());
    }
}
