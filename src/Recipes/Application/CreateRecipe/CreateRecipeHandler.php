<?php

declare(strict_types=1);

namespace App\Recipes\Application\CreateRecipe;

use App\Recipes\Application\IngredientResolver;
use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeDescription;
use App\Recipes\Domain\RecipeRepository;
use App\Recipes\Domain\RecipeTitle;

final class CreateRecipeHandler
{
    public function __construct(
        private readonly RecipeRepository $recipes,
        private readonly IngredientResolver $ingredients,
    ) {
    }

    public function __invoke(CreateRecipe $command): RecipeView
    {
        $recipe = new Recipe(
            $this->recipes->nextIdentity(),
            new RecipeTitle($command->title),
            new RecipeDescription($command->description),
            ($this->ingredients)($command->ingredients),
        );
        $this->recipes->save($recipe);

        return RecipeView::fromRecipe($recipe);
    }
}
