<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Catalog\Domain\ProductId;

final class ProductNotFound extends \DomainException
{
    public static function withId(ProductId $id): self
    {
        return new self(\sprintf('Product "%s" was not found.', $id->toString()));
    }
}
