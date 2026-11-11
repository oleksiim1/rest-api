<?php

declare(strict_types=1);

namespace App\Recipes\Application;

use App\Recipes\Domain\Ingredient;
use App\Recipes\Domain\Recipe;

final class RecipeView
{
    /**
     * @param list<array{productId: string, quantity: float}> $ingredients
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly array $ingredients,
    ) {
    }

    public static function fromRecipe(Recipe $recipe): self
    {
        return new self(
            $recipe->id()->toString(),
            $recipe->title()->value(),
            $recipe->description()->value(),
            array_map(
                static fn (Ingredient $ingredient): array => [
                    'productId' => $ingredient->productId()->toString(),
                    'quantity' => $ingredient->quantity()->toFloat(),
                ],
                $recipe->ingredients(),
            ),
        );
    }

    /**
     * @return array{id: string, title: string, description: string, ingredients: list<array{productId: string, quantity: float}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
        ];
    }
}
