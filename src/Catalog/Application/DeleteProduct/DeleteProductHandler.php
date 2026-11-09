<?php

declare(strict_types=1);

namespace App\Catalog\Application\DeleteProduct;

use App\Catalog\Domain\Exception\ProductNotFound;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductRepository;

final class DeleteProductHandler
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function __invoke(DeleteProduct $command): void
    {
        $id = ProductId::fromString($command->id);
        $product = $this->products->ofId($id) ?? throw ProductNotFound::withId($id);

        $this->products->remove($product);
    }
}
