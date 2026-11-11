<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;
use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductRepository;
use App\Tests\TruncatesTables;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineProductRepositoryTest extends KernelTestCase
{
    use TruncatesTables;

    private ProductRepository $products;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::truncateTables($container, 'products');

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

    public function testPageIsOrderedByName(): void
    {
        $this->newProduct('Rice');
        $this->newProduct('Eggs', Unit::Dozen);
        $this->newProduct('Garlic');

        self::assertSame(['Eggs', 'Garlic', 'Rice'], $this->names($this->products->page(0, 10)));
    }

    public function testPageAppliesOffsetAndLimit(): void
    {
        foreach (['Rice', 'Eggs', 'Garlic', 'Potato'] as $name) {
            $this->newProduct($name);
        }

        self::assertSame(['Garlic', 'Potato'], $this->names($this->products->page(1, 2)));
        self::assertSame([], $this->names($this->products->page(4, 2)));
    }

    public function testSavingACaseVariantDuplicateThrowsProductAlreadyExists(): void
    {
        $this->newProduct('Potato');

        // Bypasses the handler's ofName() check, as a concurrent request would.
        try {
            $this->newProduct('POTATO');
            self::fail('Expected ProductAlreadyExists.');
        } catch (ProductAlreadyExists $e) {
            self::assertSame('A product named "POTATO" already exists.', $e->getMessage());
            self::assertInstanceOf(UniqueConstraintViolationException::class, $e->getPrevious());
        }
    }

    public function testRenamingOntoAnExistingNameThrowsProductAlreadyExists(): void
    {
        $this->newProduct('Rice');
        $potato = $this->newProduct('Potato');

        $this->expectException(ProductAlreadyExists::class);

        $potato->update(new ProductName('rice'), new Money(1000, 'UAH'), Unit::Kilogram);
        $this->products->save($potato);
    }

    public function testTheRepositoryKeepsWorkingAfterAConflict(): void
    {
        $this->newProduct('Potato');

        try {
            $this->newProduct('potato');
        } catch (ProductAlreadyExists) {
        }

        // A failed flush closes the EntityManager; without a reset this save would throw.
        $this->newProduct('Rice');

        self::assertSame(['Potato', 'Rice'], $this->names($this->products->page(0, 10)));
    }

    /**
     * @param list<Product> $products
     *
     * @return list<string>
     */
    private function names(array $products): array
    {
        return array_map(static fn (Product $p): string => $p->name()->value(), $products);
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
