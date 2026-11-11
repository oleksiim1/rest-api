<?php

declare(strict_types=1);

namespace App\Recipes\Application;

final class IngredientInput
{
    public function __construct(
        public readonly string $productId,
        public readonly int|float $quantity,
    ) {
    }
}
