<?php

declare(strict_types=1);

namespace App\Recipes\Application\SearchRecipes;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;

final class SearchRecipesHandler
{
    public function __construct(private readonly RecipeSearchIndex $index)
    {
    }

    /**
     * @return list<RecipeView> best matches first
     */
    public function __invoke(SearchRecipes $query): array
    {
        return $this->index->search($query->query, $query->page);
    }
}
