<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Catalog;

use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;
use App\Recipes\Domain\ProductId;
use App\Recipes\Infrastructure\Catalog\CatalogProductExistence;
use App\Tests\Catalog\TruncatesProducts;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CatalogProductExistenceTest extends KernelTestCase
{
    use TruncatesProducts;

    private ProductRepository $products;
    private CatalogProductExistence $existence;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::truncateProducts($container);

        $this->products = $container->get(ProductRepository::class);
        $this->existence = new CatalogProductExistence($this->products);
    }

    public function testAProductInTheCatalogExists(): void
    {
        $id = $this->products->nextIdentity();
        $this->products->save(new Product($id, new ProductName('Potato'), new Money(2500, 'UAH'), Unit::Kilogram));

        self::assertTrue($this->existence->exists(ProductId::fromString(strtoupper($id->toString()))));
    }

    public function testAProductMissingFromTheCatalogDoesNotExist(): void
    {
        self::assertFalse($this->existence->exists(ProductId::fromString($this->products->nextIdentity()->toString())));
    }
}
