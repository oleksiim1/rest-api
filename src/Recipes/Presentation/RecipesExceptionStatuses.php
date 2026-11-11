<?php

declare(strict_types=1);

namespace App\Recipes\Presentation;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Recipes\Domain\Exception\InvalidRecipeId;
use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Recipes\Domain\Exception\UnknownProduct;
use App\Shared\Presentation\ExceptionStatusMapping;

final class RecipesExceptionStatuses implements ExceptionStatusMapping
{
    public function statuses(): array
    {
        return [
            RecipeNotFound::class => 404,
            // A malformed id cannot name an existing recipe.
            InvalidRecipeId::class => 404,
            InvalidRecipeData::class => 422,
            // The recipe exists (or is being created); it is its content that refers to nothing.
            UnknownProduct::class => 422,
        ];
    }
}
