<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListProducts;

use App\Shared\Application\PageRequest;

final class ListProducts
{
    public function __construct(public readonly PageRequest $page = new PageRequest())
    {
    }
}
