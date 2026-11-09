<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListProducts;

use App\Catalog\Application\ProductView;
use App\Catalog\Domain\ProductRepository;

final class ListProductsHandler
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    /**
     * @return list<ProductView>
     */
    public function __invoke(): array
    {
        return array_map(ProductView::fromProduct(...), $this->products->all());
    }
}
