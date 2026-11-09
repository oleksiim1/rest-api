<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Application\UpdateProduct\UpdateProduct;
use App\Catalog\Application\UpdateProduct\UpdateProductHandler;
use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Exception\ProductNotFound;
use PHPUnit\Framework\TestCase;

final class UpdateProductHandlerTest extends TestCase
{
    private InMemoryProductRepository $products;
    private CreateProductHandler $create;
    private UpdateProductHandler $handler;

    protected function setUp(): void
    {
        $this->products = new InMemoryProductRepository();
        $this->create = new CreateProductHandler($this->products);
        $this->handler = new UpdateProductHandler($this->products);
    }

    public function testItReplacesTheProductData(): void
    {
        $eggs = ($this->create)(new CreateProduct('Eggs', 7000, 'UAH', 'dozen'));

        $view = ($this->handler)(new UpdateProduct($eggs->id, 'Eggs', 7500, 'UAH', 'piece'));

        self::assertSame($eggs->id, $view->id);
        self::assertSame(7500, $view->priceAmount);
        self::assertSame('piece', $view->unit);
    }

    public function testItAllowsKeepingTheSameNameWithDifferentCase(): void
    {
        $eggs = ($this->create)(new CreateProduct('Eggs', 7000, 'UAH', 'dozen'));

        $view = ($this->handler)(new UpdateProduct($eggs->id, 'EGGS', 7000, 'UAH', 'dozen'));

        self::assertSame('EGGS', $view->name);
    }

    public function testItRejectsANameTakenByAnotherProduct(): void
    {
        ($this->create)(new CreateProduct('Rice', 6500, 'UAH', 'kg'));
        $eggs = ($this->create)(new CreateProduct('Eggs', 7000, 'UAH', 'dozen'));

        $this->expectException(ProductAlreadyExists::class);

        ($this->handler)(new UpdateProduct($eggs->id, 'rice', 7000, 'UAH', 'dozen'));
    }

    public function testItFailsForAnUnknownProduct(): void
    {
        $this->expectException(ProductNotFound::class);

        ($this->handler)(new UpdateProduct('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b', 'Eggs', 7000, 'UAH', 'dozen'));
    }

    public function testItFailsForAMalformedId(): void
    {
        $this->expectException(InvalidProductId::class);

        ($this->handler)(new UpdateProduct('nope', 'Eggs', 7000, 'UAH', 'dozen'));
    }
}
