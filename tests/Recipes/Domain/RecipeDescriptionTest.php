<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Recipes\Domain\RecipeDescription;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecipeDescriptionTest extends TestCase
{
    public function testItTrimsTheValueButKeepsInnerLineBreaks(): void
    {
        self::assertSame("Boil.\n\nServe.", (new RecipeDescription("\n Boil.\n\nServe.  "))->value());
    }

    public function testItAcceptsFiveThousandCharacters(): void
    {
        self::assertSame(5000, mb_strlen((new RecipeDescription(str_repeat('ї', 5000)))->value()));
    }

    #[DataProvider('invalidDescriptions')]
    public function testItRejectsInvalidDescriptions(string $description, string $message): void
    {
        $this->expectException(InvalidRecipeData::class);
        $this->expectExceptionMessage($message);

        new RecipeDescription($description);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidDescriptions(): iterable
    {
        yield 'empty' => ['', 'Recipe description must not be empty.'];
        yield 'whitespace only' => [" \n\t ", 'Recipe description must not be empty.'];
        yield 'too long' => [str_repeat('a', 5001), 'Recipe description must be at most 5000 characters.'];
    }
}
