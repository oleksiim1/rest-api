<?php

declare(strict_types=1);

namespace App\Recipes\Application\DeleteRecipe;

final class DeleteRecipe
{
    public function __construct(public readonly string $id)
    {
    }
}
