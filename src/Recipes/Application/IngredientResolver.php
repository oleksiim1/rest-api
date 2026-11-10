<?php

declare(strict_types=1);

namespace App\Recipes\Application;

use App\Recipes\Domain\Exception\UnknownProduct;
use App\Recipes\Domain\Ingredient;
use App\Recipes\Domain\ProductId;
use App\Recipes\Domain\Quantity;

/**
 * Turns ingredient input into domain Ingredients, shared by the create and update use cases.
 */
final class IngredientResolver
{
    public function __construct(private readonly ProductExistence $products)
    {
    }

    /**
     * @param list<IngredientInput> $inputs
     *
     * @return list<Ingredient>
     */
    public function __invoke(array $inputs): array
    {
        $ingredients = [];
        foreach ($inputs as $input) {
            $productId = ProductId::fromString($input->productId);
            $quantity = Quantity::fromNumber($input->quantity);

            if (!$this->products->exists($productId)) {
                throw UnknownProduct::withId($productId);
            }

            $ingredients[] = new Ingredient($productId, $quantity);
        }

        return $ingredients;
    }
}
