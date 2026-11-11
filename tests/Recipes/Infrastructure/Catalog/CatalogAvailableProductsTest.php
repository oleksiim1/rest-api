<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Catalog;

use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;
use App\Recipes\Domain\ProductId;
use App\Recipes\Infrastructure\Catalog\CatalogAvailableProducts;
use App\Tests\TruncatesTables;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CatalogAvailableProductsTest extends KernelTestCase
{
    use TruncatesTables;

    private ProductRepository $products;
    private CatalogAvailableProducts $available;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::truncateTables($container, 'products');

        $this->products = $container->get(ProductRepository::class);
        $this->available = new CatalogAvailableProducts($this->products);
    }

    public function testItListsEveryCatalogProductOrderedByName(): void
    {
        $rice = $this->save('Rice');
        $eggs = $this->save('Eggs');

        self::assertSame(
            [$eggs, $rice],
            array_map(static fn (ProductId $id): string => $id->toString(), $this->available->ids()),
        );
    }

    public function testAnEmptyCatalogHasNoProducts(): void
    {
        self::assertSame([], $this->available->ids());
    }

    private function save(string $name): string
    {
        $id = $this->products->nextIdentity();
        $this->products->save(new Product($id, new ProductName($name), new Money(2500, 'UAH'), Unit::Kilogram));

        return $id->toString();
    }
}
