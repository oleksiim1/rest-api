<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Catalog\Domain\ProductName;

final class ProductAlreadyExists extends \DomainException
{
    public static function withName(ProductName $name): self
    {
        return new self(\sprintf('A product named "%s" already exists.', $name->value()));
    }
}
