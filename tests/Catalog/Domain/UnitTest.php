<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductData;
use App\Catalog\Domain\Unit;
use PHPUnit\Framework\TestCase;

final class UnitTest extends TestCase
{
    public function testItParsesAKnownCode(): void
    {
        self::assertSame(Unit::Dozen, Unit::fromCode('dozen'));
    }

    public function testItRejectsAnUnknownCode(): void
    {
        $this->expectException(InvalidProductData::class);
        $this->expectExceptionMessage('kg, g, l, ml, piece, dozen, pack');

        Unit::fromCode('bucket');
    }
}
