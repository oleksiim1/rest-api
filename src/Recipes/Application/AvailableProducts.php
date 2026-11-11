<?php

declare(strict_types=1);

namespace App\Recipes\Application;

use App\Recipes\Domain\ProductId;

/**
 * Lists the Catalog products a recipe can use, without the Recipes context depending on the Catalog.
 */
interface AvailableProducts
{
    /**
     * @return list<ProductId> in a stable order
     */
    public function ids(): array;
}
