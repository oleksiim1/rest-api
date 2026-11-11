<?php

declare(strict_types=1);

namespace App\Recipes\Application\SearchRecipes;

use App\Shared\Application\PageRequest;

final class SearchRecipes
{
    public function __construct(
        public readonly string $query,
        public readonly PageRequest $page = new PageRequest(),
    ) {
    }
}
