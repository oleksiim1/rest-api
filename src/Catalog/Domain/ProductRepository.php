<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

interface ProductRepository
{
    public function nextIdentity(): ProductId;

    public function save(Product $product): void;

    public function remove(Product $product): void;

    public function ofId(ProductId $id): ?Product;

    /**
     * Case-insensitive lookup, matching ProductName::equals().
     */
    public function ofName(ProductName $name): ?Product;

    /**
     * @return list<Product> ordered by name
     */
    public function all(): array;
}
