<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\IngredientInput;
use App\Recipes\Application\IngredientResolver;
use App\Recipes\Domain\Exception\UnknownProduct;
use App\Recipes\Domain\RecipeId;
use PHPUnit\Framework\TestCase;

final class CreateRecipeHandlerTest extends TestCase
{
    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const ONION = '00000000-0000-7000-8000-000000000002';

    private InMemoryRecipeRepository $recipes;
    private InMemoryRecipeSearchIndex $index;
    private CreateRecipeHandler $handler;

    protected function setUp(): void
    {
        $this->recipes = new InMemoryRecipeRepository();
        $this->index = new InMemoryRecipeSearchIndex();
        $this->handler = new CreateRecipeHandler(
            $this->recipes,
            new IngredientResolver(new InMemoryProductExistence(self::POTATO, self::ONION)),
            $this->index,
        );
    }

    public function testItCreatesARecipe(): void
    {
        $view = ($this->handler)(new CreateRecipe('Mashed potatoes', 'Boil and mash.', [
            new IngredientInput(self::POTATO, 1.5),
            new IngredientInput(self::ONION, 1),
        ]));

        self::assertSame([
            'id' => $view->id,
            'title' => 'Mashed potatoes',
            'description' => 'Boil and mash.',
            'ingredients' => [
                ['productId' => self::POTATO, 'quantity' => 1.5],
                ['productId' => self::ONION, 'quantity' => 1.0],
            ],
        ], $view->toArray());
        self::assertNotNull($this->recipes->ofId(RecipeId::fromString($view->id)));
    }

    public function testItIndexesTheNewRecipe(): void
    {
        $view = ($this->handler)(new CreateRecipe('Mashed potatoes', 'Boil and mash.', [
            new IngredientInput(self::POTATO, 1),
        ]));

        self::assertEquals([$view->id => $view], $this->index->all());
    }

    public function testItRejectsAnIngredientWhoseProductDoesNotExist(): void
    {
        $missing = '00000000-0000-7000-8000-000000000099';

        try {
            ($this->handler)(new CreateRecipe('Mashed potatoes', 'Boil and mash.', [
                new IngredientInput(self::POTATO, 1),
                new IngredientInput($missing, 1),
            ]));
            self::fail('Expected UnknownProduct.');
        } catch (UnknownProduct $e) {
            self::assertSame(\sprintf('Product "%s" does not exist.', $missing), $e->getMessage());
        }

        self::assertSame([], $this->recipes->page(0, 10));
        self::assertSame([], $this->index->all());
    }
}
