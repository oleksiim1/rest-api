<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

interface RecipeRepository
{
    public function nextIdentity(): RecipeId;

    public function save(Recipe $recipe): void;

    public function remove(Recipe $recipe): void;

    public function ofId(RecipeId $id): ?Recipe;

    /**
     * @return list<Recipe> ordered by title, then id
     */
    public function page(int $offset, int $limit): array;

    /**
     * Every recipe, ordered by title, then id, read a batch at a time. Use each recipe only while
     * iterating: implementations may release a batch once the next one is read, so don't keep
     * or change them.
     *
     * @return iterable<Recipe>
     */
    public function all(): iterable;
}
