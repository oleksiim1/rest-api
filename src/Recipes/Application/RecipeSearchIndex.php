<?php

declare(strict_types=1);

namespace App\Recipes\Application;

use App\Shared\Application\PageRequest;

/**
 * A full-text index of recipes, kept next to the repository so that search does not read from it.
 */
interface RecipeSearchIndex
{
    /**
     * Adds the recipe, or replaces it when its id is already indexed.
     */
    public function index(RecipeView $recipe): void;

    /**
     * Removes the recipe; does nothing when it is not indexed.
     */
    public function remove(string $id): void;

    /**
     * @return list<RecipeView> best matches first
     */
    public function search(string $query, PageRequest $page): array;

    /**
     * Drops the index, recreates it empty and loads the given recipes into it.
     *
     * @param iterable<RecipeView> $recipes
     */
    public function rebuild(iterable $recipes): void;
}
