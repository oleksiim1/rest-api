<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Domain\RecipeRepository;

final class InMemoryRecipeRepository implements RecipeRepository
{
    /** @var array<string, Recipe> */
    private array $recipes = [];

    private int $sequence = 0;

    public function nextIdentity(): RecipeId
    {
        return RecipeId::fromString(\sprintf('00000000-0000-7000-9000-%012d', ++$this->sequence));
    }

    public function save(Recipe $recipe): void
    {
        $this->recipes[$recipe->id()->toString()] = $recipe;
    }

    public function remove(Recipe $recipe): void
    {
        unset($this->recipes[$recipe->id()->toString()]);
    }

    public function ofId(RecipeId $id): ?Recipe
    {
        return $this->recipes[$id->toString()] ?? null;
    }

    public function page(int $offset, int $limit): array
    {
        $recipes = array_values($this->recipes);
        usort($recipes, static fn (Recipe $a, Recipe $b): int => [$a->title()->value(), $a->id()->toString()] <=> [$b->title()->value(), $b->id()->toString()]);

        return \array_slice($recipes, $offset, $limit);
    }

    public function all(): iterable
    {
        return $this->page(0, \count($this->recipes));
    }
}
