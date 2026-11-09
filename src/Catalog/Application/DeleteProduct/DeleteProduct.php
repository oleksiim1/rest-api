<?php

declare(strict_types=1);

namespace App\Catalog\Application\DeleteProduct;

final class DeleteProduct
{
    public function __construct(public readonly string $id)
    {
    }
}
