<?php

declare(strict_types=1);

namespace App\Recipes\Infrastructure\Catalog;

use App\Catalog\Domain\ProductId as CatalogProductId;
use App\Catalog\Domain\ProductRepository;
use App\Recipes\Application\ProductExistence;
use App\Recipes\Domain\ProductId;

/**
 * Answers the Recipes context's ProductExistence port from the Catalog. This adapter is the only
 * place where Recipes code touches the Catalog.
 */
final class CatalogProductExistence implements ProductExistence
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function exists(ProductId $id): bool
    {
        // Both ids are validated, lower-cased UUIDs, so the conversion cannot fail.
        return null !== $this->products->ofId(CatalogProductId::fromString($id->toString()));
    }
}
