<?php

declare(strict_types=1);

namespace App\Recipes\Domain\Exception;

use App\Recipes\Domain\ProductId;

/**
 * An ingredient refers to a product the Catalog does not have.
 */
final class UnknownProduct extends \DomainException
{
    public static function withId(ProductId $id): self
    {
        return new self(\sprintf('Product "%s" does not exist.', $id->toString()));
    }
}
