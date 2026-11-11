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
}
