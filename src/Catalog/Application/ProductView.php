<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Domain\Product;

final class ProductView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly string $unit,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        return new self(
            $product->id()->toString(),
            $product->name()->value(),
            $product->price()->amount(),
            $product->price()->currency(),
            $product->unit()->value,
        );
    }

    /**
     * @return array{id: string, name: string, price: array{amount: int, currency: string}, unit: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => ['amount' => $this->priceAmount, 'currency' => $this->priceCurrency],
            'unit' => $this->unit,
        ];
    }
}
