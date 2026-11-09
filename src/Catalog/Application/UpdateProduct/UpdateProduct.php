<?php

declare(strict_types=1);

namespace App\Catalog\Application\UpdateProduct;

final class UpdateProduct
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly string $unit,
    ) {
    }
}
