<?php

declare(strict_types=1);

namespace App\Recipes\Application;

use App\Recipes\Domain\ProductId;

/**
 * Tells whether a Catalog product exists, without the Recipes context depending on the Catalog.
 */
interface ProductExistence
{
    public function exists(ProductId $id): bool;
}
