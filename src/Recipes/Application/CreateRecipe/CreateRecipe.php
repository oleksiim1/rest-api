<?php

declare(strict_types=1);

namespace App\Recipes\Application\CreateRecipe;

use App\Recipes\Application\IngredientInput;

final class CreateRecipe
{
    /**
     * @param list<IngredientInput> $ingredients
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly array $ingredients,
    ) {
    }
}
