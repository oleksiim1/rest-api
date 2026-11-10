<?php

declare(strict_types=1);

namespace App\Recipes\Application\UpdateRecipe;

use App\Recipes\Application\IngredientInput;

final class UpdateRecipe
{
    /**
     * @param list<IngredientInput> $ingredients
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly array $ingredients,
    ) {
    }
}
