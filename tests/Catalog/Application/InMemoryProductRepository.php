<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Application;

use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;

final class InMemoryProductRepository implements ProductRepository
{
    /** @var array<string, Product> */
    private array $products = [];

    private int $sequence = 0;

    public function nextIdentity(): ProductId
    {
        return ProductId::fromString(\sprintf('00000000-0000-7000-8000-%012d', ++$this->sequence));
    }

    public function save(Product $product): void
    {
        $sameName = $this->ofName($product->name());
        if (null !== $sameName && !$sameName->id()->equals($product->id())) {
            throw ProductAlreadyExists::withName($product->name());
        }

        $this->products[$product->id()->toString()] = $product;
    }

    public function remove(Product $product): void
    {
        unset($this->products[$product->id()->toString()]);
    }

    public function ofId(ProductId $id): ?Product
    {
        return $this->products[$id->toString()] ?? null;
    }

    public function ofName(ProductName $name): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->name()->equals($name)) {
                return $product;
            }
        }

        return null;
    }

    public function page(int $offset, int $limit): array
    {
        $products = array_values($this->products);
        usort($products, static fn (Product $a, Product $b): int => strcmp($a->name()->value(), $b->name()->value()));

        return \array_slice($products, $offset, $limit);
    }
}
