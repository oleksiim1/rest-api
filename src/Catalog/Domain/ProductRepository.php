<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Catalog\Domain\Exception\ProductAlreadyExists;

interface ProductRepository
{
    public function nextIdentity(): ProductId;

    /**
     * Implementations enforce name uniqueness themselves, so a duplicate that slips past an
     * ofName() check (e.g. a concurrent request) still fails here.
     *
     * @throws ProductAlreadyExists when another product already has the same name regardless of case
     */
    public function save(Product $product): void;

    public function remove(Product $product): void;

    public function ofId(ProductId $id): ?Product;

    /**
     * Case-insensitive lookup, matching ProductName::equals(). For a few Unicode characters the
     * storage's case folding may differ from PHP's; save() is the authoritative check.
     */
    public function ofName(ProductName $name): ?Product;

    /**
     * @return list<Product> ordered by name
     */
    public function page(int $offset, int $limit): array;
}
