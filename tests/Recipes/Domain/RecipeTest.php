<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Recipes\Domain\Ingredient;
use App\Recipes\Domain\ProductId;
use App\Recipes\Domain\Quantity;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeDescription;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Domain\RecipeTitle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecipeTest extends TestCase
{
    private const ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';
    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const ONION = '00000000-0000-7000-8000-000000000002';
    private const BEET = '00000000-0000-7000-8000-000000000003';

    public function testItExposesItsDataWithIngredientsInTheGivenOrder(): void
    {
        $recipe = self::recipe([self::ingredient(self::ONION, 0.2), self::ingredient(self::POTATO, 1.5)]);

        self::assertSame(self::ID, $recipe->id()->toString());
        self::assertSame('Mashed potatoes', $recipe->title()->value());
        self::assertSame('Boil and mash.', $recipe->description()->value());
        self::assertSame([[self::ONION, '0.200'], [self::POTATO, '1.500']], self::flatten($recipe->ingredients()));
    }

    public function testItRejectsARecipeWithoutIngredients(): void
    {
        $this->expectException(InvalidRecipeData::class);
        $this->expectExceptionMessage('A recipe needs at least one ingredient.');

        self::recipe([]);
    }

    public function testItRejectsTheSameProductTwice(): void
    {
        $this->expectException(InvalidRecipeData::class);
        $this->expectExceptionMessage(\sprintf('Product "%s" appears more than once in the recipe.', self::POTATO));

        self::recipe([
            self::ingredient(self::POTATO, 1),
            self::ingredient(self::ONION, 0.2),
            self::ingredient(strtoupper(self::POTATO), 0.5),
        ]);
    }

    public function testUpdateReplacesTitleDescriptionAndIngredients(): void
    {
        $recipe = self::recipe([self::ingredient(self::POTATO, 1), self::ingredient(self::ONION, 0.2)]);

        $recipe->update(
            new RecipeTitle('Borscht'),
            new RecipeDescription('Simmer for an hour.'),
            [self::ingredient(self::BEET, 0.5), self::ingredient(self::POTATO, 2)],
        );

        self::assertSame(self::ID, $recipe->id()->toString());
        self::assertSame('Borscht', $recipe->title()->value());
        self::assertSame('Simmer for an hour.', $recipe->description()->value());
        self::assertSame([[self::BEET, '0.500'], [self::POTATO, '2.000']], self::flatten($recipe->ingredients()));
    }

    /**
     * @param list<Ingredient> $ingredients
     */
    #[DataProvider('invalidIngredientLists')]
    public function testAFailedUpdateLeavesTheRecipeUnchanged(array $ingredients, string $message): void
    {
        $recipe = self::recipe([self::ingredient(self::POTATO, 1)]);

        try {
            $recipe->update(new RecipeTitle('Borscht'), new RecipeDescription('Simmer.'), $ingredients);
            self::fail('Expected InvalidRecipeData.');
        } catch (InvalidRecipeData $e) {
            self::assertSame($message, $e->getMessage());
        }

        self::assertSame('Mashed potatoes', $recipe->title()->value());
        self::assertSame('Boil and mash.', $recipe->description()->value());
        self::assertSame([[self::POTATO, '1.000']], self::flatten($recipe->ingredients()));
    }

    /** @return iterable<string, array{list<Ingredient>, string}> */
    public static function invalidIngredientLists(): iterable
    {
        yield 'no ingredients' => [[], 'A recipe needs at least one ingredient.'];
        yield 'duplicate product' => [
            [self::ingredient(self::BEET, 1), self::ingredient(self::BEET, 2)],
            \sprintf('Product "%s" appears more than once in the recipe.', self::BEET),
        ];
    }

    /**
     * @param list<Ingredient> $ingredients
     */
    private static function recipe(array $ingredients): Recipe
    {
        return new Recipe(
            RecipeId::fromString(self::ID),
            new RecipeTitle('Mashed potatoes'),
            new RecipeDescription('Boil and mash.'),
            $ingredients,
        );
    }

    private static function ingredient(string $productId, int|float $quantity): Ingredient
    {
        return new Ingredient(ProductId::fromString($productId), Quantity::fromNumber($quantity));
    }

    /**
     * @param list<Ingredient> $ingredients
     *
     * @return list<array{string, string}>
     */
    private static function flatten(array $ingredients): array
    {
        return array_map(
            static fn (Ingredient $i): array => [$i->productId()->toString(), $i->quantity()->value()],
            $ingredients,
        );
    }
}
