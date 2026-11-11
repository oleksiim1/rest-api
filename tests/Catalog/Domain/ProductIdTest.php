<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\ProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
{
    public function testItAcceptsAUuidAndNormalisesToLowercase(): void
    {
        $id = ProductId::fromString('0193A2B4-5C6D-7E8F-9A0B-1C2D3E4F5A6B');

        self::assertSame('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b', $id->toString());
    }

    public function testEqualIdsAreEqual(): void
    {
        $a = ProductId::fromString('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b');

        self::assertTrue($a->equals(ProductId::fromString('0193A2B4-5C6D-7E8F-9A0B-1C2D3E4F5A6B')));
        self::assertFalse($a->equals(ProductId::fromString('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6c')));
    }

    #[DataProvider('malformedValues')]
    public function testItRejectsANonUuid(string $value): void
    {
        $this->expectException(InvalidProductId::class);

        ProductId::fromString($value);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedValues(): iterable
    {
        yield 'not a uuid' => ['42'];
        yield 'trailing newline' => ["0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b\n"];
    }
}
