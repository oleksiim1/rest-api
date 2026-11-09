<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetProduct;

final class GetProduct
{
    public function __construct(public readonly string $id)
    {
    }
}
