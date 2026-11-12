<?php

declare(strict_types=1);

namespace App\Recipes\Application\ReindexRecipes;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\RecipeRepository;

/**
 * Rebuilds the search index from the repository, which fixes any drift between the two.
 */
final class ReindexRecipesHandler
{
    public function __construct(
        private readonly RecipeRepository $recipes,
        private readonly RecipeSearchIndex $index,
    ) {
    }

    /**
     * @return int how many recipes were indexed
     */
    public function __invoke(ReindexRecipes $command): int
    {
        return $this->index->rebuild($this->views());
    }

    /**
     * Streams the views, so the repository can release each batch of recipes once it is indexed.
     *
     * @return \Generator<RecipeView>
     */
    private function views(): \Generator
    {
        foreach ($this->recipes->all() as $recipe) {
            yield RecipeView::fromRecipe($recipe);
        }
    }
}
