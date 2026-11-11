<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\DeleteRecipe\DeleteRecipe;
use App\Recipes\Application\DeleteRecipe\DeleteRecipeHandler;
use App\Recipes\Application\IngredientInput;
use App\Recipes\Application\IngredientResolver;
use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Recipes\Domain\RecipeId;
use PHPUnit\Framework\TestCase;

final class DeleteRecipeHandlerTest extends TestCase
{
    private const POTATO = '00000000-0000-7000-8000-000000000001';

    private InMemoryRecipeRepository $recipes;
    private DeleteRecipeHandler $handler;

    protected function setUp(): void
    {
        $this->recipes = new InMemoryRecipeRepository();
        $this->handler = new DeleteRecipeHandler($this->recipes);
    }

    public function testItRemovesTheRecipe(): void
    {
        $create = new CreateRecipeHandler($this->recipes, new IngredientResolver(new InMemoryProductExistence(self::POTATO)));
        $view = $create(new CreateRecipe('Mashed potatoes', 'Boil and mash.', [new IngredientInput(self::POTATO, 1)]));

        ($this->handler)(new DeleteRecipe($view->id));

        self::assertNull($this->recipes->ofId(RecipeId::fromString($view->id)));
    }

    public function testItFailsForAnUnknownRecipe(): void
    {
        $this->expectException(RecipeNotFound::class);

        ($this->handler)(new DeleteRecipe('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b'));
    }
}
