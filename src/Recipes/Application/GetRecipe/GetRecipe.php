<?php

declare(strict_types=1);

namespace App\Recipes\Application\GetRecipe;

final class GetRecipe
{
    public function __construct(public readonly string $id)
    {
    }
}
