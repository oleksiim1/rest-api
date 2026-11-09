<?php

declare(strict_types=1);

namespace App\Catalog\Application\CreateProduct;

final class CreateProduct
{
    public function __construct(
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly string $unit,
    ) {
    }
}
