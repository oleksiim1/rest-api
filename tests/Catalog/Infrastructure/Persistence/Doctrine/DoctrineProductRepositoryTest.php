<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;
use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductRepository;
use App\Tests\Catalog\TruncatesProducts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineProductRepositoryTest extends KernelTestCase
{
    use TruncatesProducts;

    private ProductRepository $products;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::truncateProducts($container);

        $this->products = $container->get(ProductRepository::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    public function testTheInterfaceIsBoundToTheDoctrineImplementation(): void
    {
        self::assertInstanceOf(DoctrineProductRepository::class, $this->products);
    }

    public function testNextIdentityIsAUuidV7(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $this->products->nextIdentity()->toString(),
        );
    }

    public function testItRoundTripsAProduct(): void
    {
        $id = $this->products->nextIdentity();
        $this->products->save(new Product($id, new ProductName('Potato'), new Money(2500, 'UAH'), Unit::Kilogram));
        $this->em->clear();

        $loaded = $this->products->ofId($id);

        self::assertNotNull($loaded);
        self::assertTrue($loaded->id()->equals($id));
        self::assertSame('Potato', $loaded->name()->value());
        self::assertSame(2500, $loaded->price()->amount());
        self::assertSame('UAH', $loaded->price()->currency());
        self::assertSame(Unit::Kilogram, $loaded->unit());
    }

    public function testItPersistsUpdates(): void
    {
        $product = $this->newProduct('Eggs', Unit::Dozen);
        $product->update(new ProductName('Eggs'), new Money(7500, 'UAH'), Unit::Piece);
        $this->products->save($product);
        $this->em->clear();

        $loaded = $this->products->ofId($product->id());

        self::assertSame(7500, $loaded?->price()->amount());
        self::assertSame(Unit::Piece, $loaded?->unit());
    }

    public function testOfIdReturnsNullForAnUnknownId(): void
    {
        self::assertNull($this->products->ofId($this->products->nextIdentity()));
    }

    public function testOfNameIsCaseInsensitive(): void
    {
        $garlic = $this->newProduct('Garlic');
        $this->em->clear();

        self::assertTrue($this->products->ofName(new ProductName('gARLIC'))?->id()->equals($garlic->id()));
        self::assertNull($this->products->ofName(new ProductName('Onion')));
    }

    public function testAllIsOrderedByName(): void
    {
        $this->newProduct('Rice');
        $this->newProduct('Eggs', Unit::Dozen);
        $this->newProduct('Garlic');

        $names = array_map(static fn (Product $p): string => $p->name()->value(), $this->products->all());

        self::assertSame(['Eggs', 'Garlic', 'Rice'], $names);
    }

    public function testRemoveDeletesTheProduct(): void
    {
        $product = $this->newProduct('Rice');

        $this->products->remove($product);
        $this->em->clear();

        self::assertNull($this->products->ofId($product->id()));
    }

    private function newProduct(string $name, Unit $unit = Unit::Kilogram): Product
    {
        $product = new Product($this->products->nextIdentity(), new ProductName($name), new Money(1000, 'UAH'), $unit);
        $this->products->save($product);

        return $product;
    }
}
