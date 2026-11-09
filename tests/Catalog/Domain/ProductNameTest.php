<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductData;
use App\Catalog\Domain\ProductName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductNameTest extends TestCase
{
    public function testItTrimsTheValue(): void
    {
        self::assertSame('Potato', (new ProductName('  Potato '))->value());
    }

    public function testItComparesCaseInsensitively(): void
    {
        self::assertTrue((new ProductName('Potato'))->equals(new ProductName('pOTATO')));
        self::assertFalse((new ProductName('Potato'))->equals(new ProductName('Rice')));
    }

    #[DataProvider('invalidNames')]
    public function testItRejectsInvalidNames(string $name): void
    {
        $this->expectException(InvalidProductData::class);

        new ProductName($name);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long' => [str_repeat('a', 101)];
    }

    public function testItAcceptsAHundredCharacters(): void
    {
        self::assertSame(100, mb_strlen((new ProductName(str_repeat('ї', 100)))->value()));
    }
}
