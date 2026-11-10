<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeId;
use App\Recipes\Domain\RecipeId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecipeIdTest extends TestCase
{
    private const ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    public function testItAcceptsAUuid(): void
    {
        self::assertSame(self::ID, RecipeId::fromString(self::ID)->toString());
    }

    public function testItNormalisesToLowerCase(): void
    {
        self::assertSame(self::ID, RecipeId::fromString(strtoupper(self::ID))->toString());
    }

    public function testItComparesByValue(): void
    {
        self::assertTrue(RecipeId::fromString(self::ID)->equals(RecipeId::fromString(strtoupper(self::ID))));
        self::assertFalse(RecipeId::fromString(self::ID)->equals(RecipeId::fromString('0193a2b4-5c6d-7e8f-9a0b-000000000000')));
    }

    public function testItCastsToItsValue(): void
    {
        self::assertSame(self::ID, (string) RecipeId::fromString(self::ID));
    }

    #[DataProvider('invalidIds')]
    public function testItRejectsMalformedValues(string $value): void
    {
        $this->expectException(InvalidRecipeId::class);
        $this->expectExceptionMessage(\sprintf('"%s" is not a valid recipe id.', $value));

        RecipeId::fromString($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'not a uuid' => ['pancakes'];
        yield 'missing a group' => ['0193a2b4-5c6d-7e8f-1c2d3e4f5a6b'];
        yield 'surrounding whitespace' => [' '.self::ID];
        yield 'trailing newline' => [self::ID."\n"];
        yield 'non-hex digit' => ['0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6g'];
    }
}
