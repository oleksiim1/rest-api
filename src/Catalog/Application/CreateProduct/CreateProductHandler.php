<?php

declare(strict_types=1);

namespace App\Catalog\Application\CreateProduct;

use App\Catalog\Application\ProductView;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;

final class CreateProductHandler
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function __invoke(CreateProduct $command): ProductView
    {
        $name = new ProductName($command->name);
        $price = new Money($command->priceAmount, $command->priceCurrency);
        $unit = Unit::fromCode($command->unit);

        if (null !== $this->products->ofName($name)) {
            throw ProductAlreadyExists::withName($name);
        }

        $product = new Product($this->products->nextIdentity(), $name, $price, $unit);
        $this->products->save($product);

        return ProductView::fromProduct($product);
    }
}
