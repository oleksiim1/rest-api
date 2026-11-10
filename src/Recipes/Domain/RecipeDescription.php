<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;

final class RecipeDescription
{
    public const MAX_LENGTH = 5000;

    private readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ('' === $value) {
            throw new InvalidRecipeData('Recipe description must not be empty.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidRecipeData(\sprintf('Recipe description must be at most %d characters.', self::MAX_LENGTH));
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
