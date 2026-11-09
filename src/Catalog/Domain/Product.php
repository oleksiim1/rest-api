<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

/**
 * Not final: Doctrine's lazy-loading proxies extend the entity (until PHP 8.4 native lazy objects).
 */
class Product
{
    public function __construct(
        private readonly ProductId $id,
        private ProductName $name,
        private Money $price,
        private Unit $unit,
    ) {
    }

    public function update(ProductName $name, Money $price, Unit $unit): void
    {
        $this->name = $name;
        $this->price = $price;
        $this->unit = $unit;
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }
}
