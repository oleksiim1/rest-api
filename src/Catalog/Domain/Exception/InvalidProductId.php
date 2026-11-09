<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

final class InvalidProductId extends \DomainException
{
    public static function fromValue(string $value): self
    {
        return new self(\sprintf('"%s" is not a valid product id.', $value));
    }
}
