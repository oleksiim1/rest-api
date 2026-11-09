<?php

declare(strict_types=1);

namespace App\Catalog\Application\UpdateProduct;

use App\Catalog\Application\ProductView;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Exception\ProductNotFound;
use App\Catalog\Domain\Money;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;

final class UpdateProductHandler
{
    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function __invoke(UpdateProduct $command): ProductView
    {
        $id = ProductId::fromString($command->id);
        $product = $this->products->ofId($id) ?? throw ProductNotFound::withId($id);

        $name = new ProductName($command->name);
        $price = new Money($command->priceAmount, $command->priceCurrency);
        $unit = Unit::fromCode($command->unit);

        $sameName = $this->products->ofName($name);
        if (null !== $sameName && !$sameName->id()->equals($id)) {
            throw ProductAlreadyExists::withName($name);
        }

        $product->update($name, $price, $unit);
        $this->products->save($product);

        return ProductView::fromProduct($product);
    }
}
