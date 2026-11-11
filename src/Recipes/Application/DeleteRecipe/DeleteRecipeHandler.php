<?php

declare(strict_types=1);

namespace App\Recipes\Application\DeleteRecipe;

use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Domain\RecipeRepository;

final class DeleteRecipeHandler
{
    public function __construct(private readonly RecipeRepository $recipes)
    {
    }

    public function __invoke(DeleteRecipe $command): void
    {
        $id = RecipeId::fromString($command->id);
        $recipe = $this->recipes->ofId($id) ?? throw RecipeNotFound::withId($id);

        $this->recipes->remove($recipe);
    }
}
