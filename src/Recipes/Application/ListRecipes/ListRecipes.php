<?php

declare(strict_types=1);

namespace App\Recipes\Application\ListRecipes;

use App\Shared\Application\PageRequest;

final class ListRecipes
{
    public function __construct(public readonly PageRequest $page = new PageRequest())
    {
    }
}
