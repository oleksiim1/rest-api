<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\GetRecipe\GetRecipe;
use App\Recipes\Application\GetRecipe\GetRecipeHandler;
use App\Recipes\Application\IngredientInput;
use App\Recipes\Application\IngredientResolver;
use App\Recipes\Application\ListRecipes\ListRecipes;
use App\Recipes\Application\ListRecipes\ListRecipesHandler;
use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\Exception\InvalidRecipeId;
use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Shared\Application\PageRequest;
use PHPUnit\Framework\TestCase;

final class RecipeQueriesTest extends TestCase
{
    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const UNKNOWN_ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    private InMemoryRecipeRepository $recipes;
    private CreateRecipeHandler $create;

    protected function setUp(): void
    {
        $this->recipes = new InMemoryRecipeRepository();
        $this->create = new CreateRecipeHandler(
            $this->recipes,
            new IngredientResolver(new InMemoryProductExistence(self::POTATO)),
            new InMemoryRecipeSearchIndex(),
        );
    }

    public function testGetReturnsTheRecipe(): void
    {
        $created = $this->createRecipe('Mashed potatoes');

        $view = (new GetRecipeHandler($this->recipes))(new GetRecipe($created->id));

        self::assertEquals($created, $view);
    }

    public function testGetFailsForAnUnknownRecipe(): void
    {
        $this->expectException(RecipeNotFound::class);
        $this->expectExceptionMessage(\sprintf('Recipe "%s" was not found.', self::UNKNOWN_ID));

        (new GetRecipeHandler($this->recipes))(new GetRecipe(self::UNKNOWN_ID));
    }

    public function testGetFailsForAMalformedId(): void
    {
        $this->expectException(InvalidRecipeId::class);

        (new GetRecipeHandler($this->recipes))(new GetRecipe('pancakes'));
    }

    public function testListReturnsRecipesOrderedByTitle(): void
    {
        $this->createRecipe('Varenyky');
        $this->createRecipe('Borscht');
        $this->createRecipe('Holubtsi');

        self::assertSame(['Borscht', 'Holubtsi', 'Varenyky'], $this->titles((new ListRecipesHandler($this->recipes))(new ListRecipes())));
    }

    public function testListIsEmptyWithoutRecipes(): void
    {
        self::assertSame([], (new ListRecipesHandler($this->recipes))(new ListRecipes()));
    }

    public function testListAppliesLimitAndOffset(): void
    {
        foreach (['Varenyky', 'Borscht', 'Holubtsi', 'Deruny'] as $title) {
            $this->createRecipe($title);
        }

        $handler = new ListRecipesHandler($this->recipes);

        self::assertSame(['Deruny', 'Holubtsi'], $this->titles($handler(new ListRecipes(new PageRequest(limit: 2, offset: 1)))));
        self::assertSame([], $handler(new ListRecipes(new PageRequest(limit: 2, offset: 4))));
    }

    /**
     * @param list<RecipeView> $views
     *
     * @return list<string>
     */
    private function titles(array $views): array
    {
        return array_map(static fn (RecipeView $view): string => $view->title, $views);
    }

    private function createRecipe(string $title): RecipeView
    {
        return ($this->create)(new CreateRecipe($title, 'Cook it.', [new IngredientInput(self::POTATO, 1)]));
    }
}
