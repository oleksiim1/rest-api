<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;
use App\Shared\Domain\Uuid;

/**
 * Refers to a Catalog product from inside a recipe. The Recipes context keeps its own type
 * instead of importing the Catalog's, so the two contexts only share the UUID string.
 */
final class ProductId
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalised = Uuid::normalise($value)
            ?? throw new InvalidRecipeData(\sprintf('"%s" is not a valid product id.', $value));

        return new self($normalised);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
