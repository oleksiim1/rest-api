<?php

declare(strict_types=1);

namespace App\Recipes\Domain\Exception;

final class InvalidRecipeId extends \DomainException
{
    public static function fromValue(string $value): self
    {
        return new self(\sprintf('"%s" is not a valid recipe id.', $value));
    }
}
