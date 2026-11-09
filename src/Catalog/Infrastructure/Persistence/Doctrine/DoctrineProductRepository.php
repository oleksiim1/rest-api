<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineProductRepository implements ProductRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function nextIdentity(): ProductId
    {
        return ProductId::fromString(Uuid::v7()->toRfc4122());
    }

    public function save(Product $product): void
    {
        $this->em->persist($product);
        $this->em->flush();
    }

    public function remove(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
    }

    public function ofId(ProductId $id): ?Product
    {
        return $this->em->find(Product::class, $id);
    }

    public function ofName(ProductName $name): ?Product
    {
        return $this->em->createQuery(
            'SELECT p FROM '.Product::class.' p WHERE LOWER(p.name.value) = LOWER(:name)'
        )
            ->setParameter('name', $name->value())
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function all(): array
    {
        return $this->em->createQuery(
            'SELECT p FROM '.Product::class.' p ORDER BY p.name.value ASC'
        )->getResult();
    }
}
