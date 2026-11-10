<?php

declare(strict_types=1);

namespace App\Recipes\Domain\Exception;

use App\Recipes\Domain\RecipeId;

final class RecipeNotFound extends \DomainException
{
    public static function withId(RecipeId $id): self
    {
        return new self(\sprintf('Recipe "%s" was not found.', $id->toString()));
    }
}
