<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Application\DeleteProduct\DeleteProduct;
use App\Catalog\Application\DeleteProduct\DeleteProductHandler;
use App\Catalog\Domain\Exception\ProductNotFound;
use App\Catalog\Domain\ProductId;
use PHPUnit\Framework\TestCase;

final class DeleteProductHandlerTest extends TestCase
{
    public function testItRemovesTheProduct(): void
    {
        $products = new InMemoryProductRepository();
        $garlic = (new CreateProductHandler($products))(new CreateProduct('Garlic', 18000, 'UAH', 'kg'));

        (new DeleteProductHandler($products))(new DeleteProduct($garlic->id));

        self::assertNull($products->ofId(ProductId::fromString($garlic->id)));
    }

    public function testItFailsForAnUnknownProduct(): void
    {
        $this->expectException(ProductNotFound::class);

        (new DeleteProductHandler(new InMemoryProductRepository()))(new DeleteProduct('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b'));
    }
}
