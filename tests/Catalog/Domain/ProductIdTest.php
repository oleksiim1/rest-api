<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\ProductId;
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

    public function testItRejectsANonUuid(): void
    {
        $this->expectException(InvalidProductId::class);

        ProductId::fromString('42');
    }
}
