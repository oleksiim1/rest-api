<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;
use App\Shared\Application\PageRequest;

/**
 * Matches the query as a case-insensitive substring of the title or description, ordered by title, then id.
 */
final class InMemoryRecipeSearchIndex implements RecipeSearchIndex
{
    /** @var array<string, RecipeView> */
    private array $recipes = [];

    public function index(RecipeView $recipe): void
    {
        $this->recipes[$recipe->id] = $recipe;
    }

    public function remove(string $id): void
    {
        unset($this->recipes[$id]);
    }

    public function search(string $query, PageRequest $page): array
    {
        $needle = mb_strtolower($query);
        $matches = array_values(array_filter(
            $this->recipes,
            static fn (RecipeView $view): bool => str_contains(mb_strtolower($view->title), $needle)
                || str_contains(mb_strtolower($view->description), $needle),
        ));
        usort($matches, static fn (RecipeView $a, RecipeView $b): int => [$a->title, $a->id] <=> [$b->title, $b->id]);

        return \array_slice($matches, $page->offset, $page->limit);
    }

    public function rebuild(iterable $recipes): void
    {
        $this->recipes = [];
        foreach ($recipes as $recipe) {
            $this->index($recipe);
        }
    }

    /**
     * @return array<string, RecipeView> keyed by id
     */
    public function all(): array
    {
        return $this->recipes;
    }
}
