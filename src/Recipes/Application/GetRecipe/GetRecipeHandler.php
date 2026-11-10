<?php

declare(strict_types=1);

namespace App\Recipes\Application\GetRecipe;

use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Domain\RecipeRepository;

final class GetRecipeHandler
{
    public function __construct(private readonly RecipeRepository $recipes)
    {
    }

    public function __invoke(GetRecipe $query): RecipeView
    {
        $id = RecipeId::fromString($query->id);
        $recipe = $this->recipes->ofId($id) ?? throw RecipeNotFound::withId($id);

        return RecipeView::fromRecipe($recipe);
    }
}
