<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\IngredientInput;
use App\Recipes\Application\IngredientResolver;
use App\Recipes\Domain\RecipeId;
use PHPUnit\Framework\TestCase;

final class CreateRecipeHandlerTest extends TestCase
{
    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const ONION = '00000000-0000-7000-8000-000000000002';

    private InMemoryRecipeRepository $recipes;
    private CreateRecipeHandler $handler;

    protected function setUp(): void
    {
        $this->recipes = new InMemoryRecipeRepository();
        $this->handler = new CreateRecipeHandler(
            $this->recipes,
            new IngredientResolver(new InMemoryProductExistence(self::POTATO, self::ONION)),
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
}
