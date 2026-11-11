<?php

declare(strict_types=1);

namespace App\Recipes\Application\ListRecipes;

use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\RecipeRepository;

final class ListRecipesHandler
{
    public function __construct(private readonly RecipeRepository $recipes)
    {
    }

    /**
     * @return list<RecipeView>
     */
    public function __invoke(ListRecipes $query): array
    {
        return array_map(RecipeView::fromRecipe(...), $this->recipes->page($query->page->offset, $query->page->limit));
    }
}
