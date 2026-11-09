<?php

declare(strict_types=1);

namespace App\Catalog\Presentation;

use App\Catalog\Domain\Exception\InvalidProductData;
use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Exception\ProductNotFound;
use App\Shared\Presentation\ExceptionStatusMapping;

final class CatalogExceptionStatuses implements ExceptionStatusMapping
{
    public function statuses(): array
    {
        return [
            ProductNotFound::class => 404,
            // A malformed id cannot name an existing product.
            InvalidProductId::class => 404,
            ProductAlreadyExists::class => 409,
            InvalidProductData::class => 422,
        ];
    }
}
