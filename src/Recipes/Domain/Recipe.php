<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;

/**
 * Not final: Doctrine's lazy-loading proxies extend the entity (until PHP 8.4 native lazy objects).
 */
class Recipe
{
    /**
     * A list while the recipe is new; Doctrine swaps in its own collection once the recipe is
     * managed, so it is only ever iterated or replaced as a whole, never mutated in place.
     *
     * @var iterable<RecipeIngredient>
     */
    private iterable $ingredients;

    /**
     * @param list<Ingredient> $ingredients
     */
    public function __construct(
        private readonly RecipeId $id,
        private RecipeTitle $title,
        private RecipeDescription $description,
        array $ingredients,
    ) {
        $this->ingredients = $this->rows($ingredients);
    }

    public function id(): RecipeId
    {
        return $this->id;
    }

    public function title(): RecipeTitle
    {
        return $this->title;
    }

    public function description(): RecipeDescription
    {
        return $this->description;
    }

    /**
     * @return list<Ingredient> in the order they were given
     */
    public function ingredients(): array
    {
        $rows = [...$this->ingredients];
        usort($rows, static fn (RecipeIngredient $a, RecipeIngredient $b): int => $a->position() <=> $b->position());

        return array_map(static fn (RecipeIngredient $row): Ingredient => $row->toIngredient(), $rows);
    }

    /**
     * @param list<Ingredient> $ingredients
     *
     * @return list<RecipeIngredient>
     */
    private function rows(array $ingredients): array
    {
        if ([] === $ingredients) {
            throw new InvalidRecipeData('A recipe needs at least one ingredient.');
        }

        $rows = [];
        $seen = [];
        foreach (array_values($ingredients) as $position => $ingredient) {
            $productId = $ingredient->productId()->toString();
            if (isset($seen[$productId])) {
                throw new InvalidRecipeData(\sprintf('Product "%s" appears more than once in the recipe.', $productId));
            }
            $seen[$productId] = true;

            $rows[] = new RecipeIngredient($this, $ingredient->productId(), $ingredient->quantity(), $position);
        }

        return $rows;
    }
}
