<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Application\GetProduct\GetProduct;
use App\Catalog\Application\GetProduct\GetProductHandler;
use App\Catalog\Application\ListProducts\ListProducts;
use App\Catalog\Application\ListProducts\ListProductsHandler;
use App\Catalog\Application\ProductView;
use App\Catalog\Domain\Exception\ProductNotFound;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductQueriesTest extends TestCase
{
    private InMemoryProductRepository $products;
    private CreateProductHandler $create;

    protected function setUp(): void
    {
        $this->products = new InMemoryProductRepository();
        $this->create = new CreateProductHandler($this->products);
    }

    public function testGetReturnsTheProduct(): void
    {
        $rice = ($this->create)(new CreateProduct('Rice', 6500, 'UAH', 'kg'));

        $view = (new GetProductHandler($this->products))(new GetProduct($rice->id));

        self::assertEquals($rice, $view);
    }

    public function testGetFailsForAnUnknownProduct(): void
    {
        $this->expectException(ProductNotFound::class);

        (new GetProductHandler($this->products))(new GetProduct('0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b'));
    }

    public function testListReturnsProductsOrderedByName(): void
    {
        ($this->create)(new CreateProduct('Rice', 6500, 'UAH', 'kg'));
        ($this->create)(new CreateProduct('Eggs', 7000, 'UAH', 'dozen'));

        $views = (new ListProductsHandler($this->products))(new ListProducts());

        self::assertSame(['Eggs', 'Rice'], array_map(static fn (ProductView $v): string => $v->name, $views));
    }

    public function testListIsEmptyWithoutProducts(): void
    {
        self::assertSame([], (new ListProductsHandler($this->products))(new ListProducts()));
    }

    public function testListReturnsTheRequestedPage(): void
    {
        foreach (['Rice', 'Eggs', 'Garlic', 'Potato'] as $name) {
            ($this->create)(new CreateProduct($name, 1000, 'UAH', 'kg'));
        }

        $views = (new ListProductsHandler($this->products))(new ListProducts(limit: 2, offset: 1));

        self::assertSame(['Garlic', 'Potato'], array_map(static fn (ProductView $v): string => $v->name, $views));
    }

    #[DataProvider('invalidPages')]
    public function testListRejectsAnInvalidPage(int $limit, int $offset): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ListProducts($limit, $offset);
    }

    /** @return iterable<string, array{int, int}> */
    public static function invalidPages(): iterable
    {
        yield 'zero limit' => [0, 0];
        yield 'limit above max' => [ListProducts::MAX_LIMIT + 1, 0];
        yield 'negative offset' => [10, -1];
    }
}
