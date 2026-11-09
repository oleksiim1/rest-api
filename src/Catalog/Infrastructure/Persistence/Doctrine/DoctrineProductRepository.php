<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class DoctrineProductRepository implements ProductRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function nextIdentity(): ProductId
    {
        return ProductId::fromString(Uuid::v7()->toRfc4122());
    }

    /**
     * The handlers check ofName() first, but two concurrent requests can both pass that check.
     * The unique index on LOWER(name) then rejects the second flush, which is reported as the
     * same ProductAlreadyExists the check would have thrown.
     *
     * A failed flush closes the EntityManager, so it is reset to keep long-lived callers
     * (e.g. the seed command's loop) working after the conflict.
     */
    public function save(Product $product): void
    {
        $em = $this->em();

        try {
            $em->persist($product);
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            $this->registry->resetManager();

            throw ProductAlreadyExists::withName($product->name(), $e);
        }
    }

    public function remove(Product $product): void
    {
        $em = $this->em();
        $em->remove($product);
        $em->flush();
    }

    public function ofId(ProductId $id): ?Product
    {
        return $this->em()->find(Product::class, $id);
    }

    public function ofName(ProductName $name): ?Product
    {
        return $this->em()->createQuery(
            'SELECT p FROM '.Product::class.' p WHERE LOWER(p.name.value) = LOWER(:name)'
        )
            ->setParameter('name', $name->value())
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function page(int $offset, int $limit): array
    {
        return $this->em()->createQuery(
            'SELECT p FROM '.Product::class.' p ORDER BY p.name.value ASC, p.id ASC'
        )
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();
    }

    private function em(): EntityManagerInterface
    {
        $em = $this->registry->getManagerForClass(Product::class);
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }
}
