<?php

declare(strict_types=1);

namespace App\Recipes\Infrastructure\Catalog;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;
use App\Recipes\Application\AvailableProducts;
use App\Recipes\Domain\ProductId;

/**
 * Answers the Recipes context's AvailableProducts port from the Catalog.
 */
final class CatalogAvailableProducts implements AvailableProducts
{
    private const BATCH = 500;

    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function ids(): array
    {
        $ids = [];

        for ($offset = 0; ; $offset += self::BATCH) {
            $page = $this->products->page($offset, self::BATCH);
            array_push($ids, ...array_map(
                static fn (Product $product): ProductId => ProductId::fromString($product->id()->toString()),
                $page,
            ));

            if (\count($page) < self::BATCH) {
                return $ids;
            }
        }
    }
}
