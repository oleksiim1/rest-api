<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeData;

/**
 * Refers to a Catalog product from inside a recipe. The Recipes context keeps its own type
 * instead of importing the Catalog's, so the two contexts only share the UUID string.
 */
final class ProductId
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D';

    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalised = strtolower($value);

        if (1 !== preg_match(self::PATTERN, $normalised)) {
            throw new InvalidRecipeData(\sprintf('"%s" is not a valid product id.', $value));
        }

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
