<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Recipes\Domain\ProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
{
    private const ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    public function testItAcceptsAUuidCaseInsensitively(): void
    {
        self::assertSame(self::ID, ProductId::fromString(strtoupper(self::ID))->toString());
    }

    public function testItComparesByValue(): void
    {
        self::assertTrue(ProductId::fromString(self::ID)->equals(ProductId::fromString(strtoupper(self::ID))));
        self::assertFalse(ProductId::fromString(self::ID)->equals(ProductId::fromString('0193a2b4-5c6d-7e8f-9a0b-000000000000')));
    }

    /**
     * A malformed product id is bad recipe data (422), not a missing resource (404).
     */
    #[DataProvider('invalidIds')]
    public function testItRejectsMalformedValuesAsInvalidRecipeData(string $value): void
    {
        $this->expectException(InvalidRecipeData::class);
        $this->expectExceptionMessage(\sprintf('"%s" is not a valid product id.', $value));

        ProductId::fromString($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'not a uuid' => ['potato'];
        yield 'trailing newline' => [self::ID."\n"];
    }
}
