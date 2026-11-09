<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Domain\Exception\InvalidProductData;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\ProductId;
use PHPUnit\Framework\TestCase;

final class CreateProductHandlerTest extends TestCase
{
    private InMemoryProductRepository $products;
    private CreateProductHandler $handler;

    protected function setUp(): void
    {
        $this->products = new InMemoryProductRepository();
        $this->handler = new CreateProductHandler($this->products);
    }

    public function testItCreatesAProduct(): void
    {
        $view = ($this->handler)(new CreateProduct('Potato', 2500, 'UAH', 'kg'));

        self::assertSame([
            'id' => $view->id,
            'name' => 'Potato',
            'price' => ['amount' => 2500, 'currency' => 'UAH'],
            'unit' => 'kg',
        ], $view->toArray());
        self::assertNotNull($this->products->ofId(ProductId::fromString($view->id)));
    }

    public function testItRejectsADuplicateNameIgnoringCase(): void
    {
        ($this->handler)(new CreateProduct('Potato', 2500, 'UAH', 'kg'));

        $this->expectException(ProductAlreadyExists::class);

        ($this->handler)(new CreateProduct('potato', 2600, 'UAH', 'kg'));
    }

    public function testItRejectsAnUnknownUnit(): void
    {
        $this->expectException(InvalidProductData::class);

        ($this->handler)(new CreateProduct('Potato', 2500, 'UAH', 'bucket'));
    }
}
