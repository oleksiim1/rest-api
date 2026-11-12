<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\IngredientInput;
use App\Recipes\Application\IngredientResolver;
use App\Recipes\Application\RecipeView;
use App\Recipes\Application\UpdateRecipe\UpdateRecipe;
use App\Recipes\Application\UpdateRecipe\UpdateRecipeHandler;
use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Recipes\Domain\Exception\UnknownProduct;
use App\Recipes\Domain\RecipeId;
use PHPUnit\Framework\TestCase;

final class UpdateRecipeHandlerTest extends TestCase
{
    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const BEET = '00000000-0000-7000-8000-000000000002';
    private const MISSING = '00000000-0000-7000-8000-000000000099';

    private InMemoryRecipeRepository $recipes;
    private InMemoryRecipeSearchIndex $index;
    private UpdateRecipeHandler $handler;
    private RecipeView $existing;

    protected function setUp(): void
    {
        $this->recipes = new InMemoryRecipeRepository();
        $this->index = new InMemoryRecipeSearchIndex();
        $resolver = new IngredientResolver(new InMemoryProductExistence(self::POTATO, self::BEET));
        $this->handler = new UpdateRecipeHandler($this->recipes, $resolver, $this->index);

        $this->existing = (new CreateRecipeHandler($this->recipes, $resolver, $this->index))(
            new CreateRecipe('Mashed potatoes', 'Boil and mash.', [new IngredientInput(self::POTATO, 1)]),
        );
    }

    public function testItReplacesTheRecipe(): void
    {
        $view = ($this->handler)(new UpdateRecipe($this->existing->id, 'Borscht', 'Simmer.', [
            new IngredientInput(self::BEET, 0.5),
            new IngredientInput(self::POTATO, 2),
        ]));

        $expected = [
            'id' => $this->existing->id,
            'title' => 'Borscht',
            'description' => 'Simmer.',
            'ingredients' => [
                ['productId' => self::BEET, 'quantity' => 0.5],
                ['productId' => self::POTATO, 'quantity' => 2.0],
            ],
        ];
        self::assertSame($expected, $view->toArray());

        $stored = $this->recipes->ofId(RecipeId::fromString($this->existing->id));
        self::assertNotNull($stored);
        self::assertSame($expected, RecipeView::fromRecipe($stored)->toArray());
    }

    public function testItReplacesTheIndexedRecipe(): void
    {
        $view = ($this->handler)(new UpdateRecipe($this->existing->id, 'Borscht', 'Simmer.', [
            new IngredientInput(self::BEET, 0.5),
        ]));

        self::assertEquals([$this->existing->id => $view], $this->index->all());
    }

    public function testItFailsForAnUnknownRecipe(): void
    {
        $this->expectException(RecipeNotFound::class);

        ($this->handler)(new UpdateRecipe('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b', 'Borscht', 'Simmer.', [
            new IngredientInput(self::BEET, 1),
        ]));
    }

    public function testItRejectsAMissingProductAndKeepsTheRecipe(): void
    {
        try {
            ($this->handler)(new UpdateRecipe($this->existing->id, 'Borscht', 'Simmer.', [
                new IngredientInput(self::MISSING, 1),
            ]));
            self::fail('Expected UnknownProduct.');
        } catch (UnknownProduct) {
        }

        $stored = $this->recipes->ofId(RecipeId::fromString($this->existing->id));
        self::assertNotNull($stored);
        self::assertEquals($this->existing, RecipeView::fromRecipe($stored));
        self::assertEquals([$this->existing->id => $this->existing], $this->index->all());
    }
}
