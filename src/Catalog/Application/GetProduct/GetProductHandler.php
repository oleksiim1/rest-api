<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetProduct;

use App\Catalog\Application\ProductView;
use App\Catalog\Domain\Exception\ProductNotFound;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductRepository;

final class GetProductHandler
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function __invoke(GetProduct $query): ProductView
    {
        $id = ProductId::fromString($query->id);
        $product = $this->products->ofId($id) ?? throw ProductNotFound::withId($id);

        return ProductView::fromProduct($product);
    }
}
