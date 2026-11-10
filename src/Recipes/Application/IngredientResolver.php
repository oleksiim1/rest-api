<?php

declare(strict_types=1);

namespace App\Recipes\Application;

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
        return array_map(
            static fn (IngredientInput $input): Ingredient => new Ingredient(
                ProductId::fromString($input->productId),
                Quantity::fromNumber($input->quantity),
            ),
            $inputs,
        );
    }
}
