<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;

final class RecipeTitle
{
    public const MAX_LENGTH = 150;

    private readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value) {
            throw new InvalidRecipeData('Recipe title must not be empty.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidRecipeData(\sprintf('Recipe title must be at most %d characters.', self::MAX_LENGTH));
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
