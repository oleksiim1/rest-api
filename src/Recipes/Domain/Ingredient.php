<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

/**
 * One line of a recipe: how much of which Catalog product it needs.
 */
final class Ingredient
{
    public function __construct(
        private readonly ProductId $productId,
        private readonly Quantity $quantity,
    ) {
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }
}
