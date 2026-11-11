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
    private const BATCH = 500;

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
        $count = 0;
        $this->index->rebuild($this->views($count));

        return $count;
    }

    /**
     * Streams the recipes a batch at a time, so the whole table is never in memory at once.
     *
     * @return \Generator<RecipeView>
     */
    private function views(int &$count): \Generator
    {
        for ($offset = 0; ; $offset += self::BATCH) {
            $page = $this->recipes->page($offset, self::BATCH);
            foreach ($page as $recipe) {
                ++$count;
                yield RecipeView::fromRecipe($recipe);
            }

            if (\count($page) < self::BATCH) {
                return;
            }
        }
    }
}
