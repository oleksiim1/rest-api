<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\RecipeView;
use App\Recipes\Application\ReindexRecipes\ReindexRecipes;
use App\Recipes\Application\ReindexRecipes\ReindexRecipesHandler;
use App\Recipes\Domain\Ingredient;
use App\Recipes\Domain\ProductId;
use App\Recipes\Domain\Quantity;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeDescription;
use App\Recipes\Domain\RecipeTitle;
use PHPUnit\Framework\TestCase;

final class ReindexRecipesHandlerTest extends TestCase
{
    // More than one batch of 500.
    private const COUNT = 1203;

    public function testItRebuildsTheIndexFromEveryRecipeAndReturnsTheCount(): void
    {
        $recipes = new InMemoryRecipeRepository();
        for ($i = 0; $i < self::COUNT; ++$i) {
            $recipes->save(new Recipe(
                $recipes->nextIdentity(),
                new RecipeTitle(\sprintf('Recipe %04d', $i)),
                new RecipeDescription('Cook it.'),
                [new Ingredient(ProductId::fromString('00000000-0000-7000-8000-000000000001'), Quantity::fromNumber(1))],
            ));
        }

        $index = new InMemoryRecipeSearchIndex();
        $stale = new RecipeView('00000000-0000-7000-9999-000000000001', 'Deleted', 'Gone.', []);
        $index->index($stale);

        $count = (new ReindexRecipesHandler($recipes, $index))(new ReindexRecipes());

        self::assertSame(self::COUNT, $count);
        $indexed = $index->all();
        self::assertCount(self::COUNT, $indexed);
        self::assertArrayNotHasKey($stale->id, $indexed);

        foreach ($recipes->page(0, self::COUNT) as $recipe) {
            self::assertEquals(RecipeView::fromRecipe($recipe), $indexed[$recipe->id()->toString()]);
        }
    }

    public function testItEmptiesTheIndexWhenThereAreNoRecipes(): void
    {
        $index = new InMemoryRecipeSearchIndex();
        $index->index(new RecipeView('00000000-0000-7000-9999-000000000001', 'Deleted', 'Gone.', []));

        $count = (new ReindexRecipesHandler(new InMemoryRecipeRepository(), $index))(new ReindexRecipes());

        self::assertSame(0, $count);
        self::assertSame([], $index->all());
    }
}
