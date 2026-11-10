<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Recipes\Domain\RecipeTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecipeTitleTest extends TestCase
{
    public function testItTrimsTheValue(): void
    {
        self::assertSame('Borscht', (new RecipeTitle("  Borscht \n"))->value());
    }

    public function testItAcceptsAHundredAndFiftyCharacters(): void
    {
        self::assertSame(150, mb_strlen((new RecipeTitle(str_repeat('ї', 150)))->value()));
    }

    #[DataProvider('invalidTitles')]
    public function testItRejectsInvalidTitles(string $title, string $message): void
    {
        $this->expectException(InvalidRecipeData::class);
        $this->expectExceptionMessage($message);

        new RecipeTitle($title);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidTitles(): iterable
    {
        yield 'empty' => ['', 'Recipe title must not be empty.'];
        yield 'whitespace only' => ['   ', 'Recipe title must not be empty.'];
        yield 'too long' => [str_repeat('a', 151), 'Recipe title must be at most 150 characters.'];
    }
}
