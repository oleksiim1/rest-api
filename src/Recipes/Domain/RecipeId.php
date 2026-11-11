<?php

declare(strict_types=1);

namespace App\Recipes\Domain;

use App\Recipes\Domain\Exception\InvalidRecipeId;
use App\Shared\Domain\Uuid;

final class RecipeId
{
    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalised = Uuid::normalise($value) ?? throw InvalidRecipeId::fromValue($value);

        return new self($normalised);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Doctrine's identity map keys entities by the string form of their id.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
