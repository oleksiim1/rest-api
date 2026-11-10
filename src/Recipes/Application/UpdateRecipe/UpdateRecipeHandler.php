<?php

declare(strict_types=1);

namespace App\Recipes\Application\UpdateRecipe;

use App\Recipes\Application\IngredientResolver;
use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\Exception\RecipeNotFound;
use App\Recipes\Domain\RecipeDescription;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Domain\RecipeRepository;
use App\Recipes\Domain\RecipeTitle;

final class UpdateRecipeHandler
{
    public function __construct(
        private readonly RecipeRepository $recipes,
        private readonly IngredientResolver $ingredients,
    ) {
    }

    public function __invoke(UpdateRecipe $command): RecipeView
    {
        $id = RecipeId::fromString($command->id);
        $recipe = $this->recipes->ofId($id) ?? throw RecipeNotFound::withId($id);

        $recipe->update(
            new RecipeTitle($command->title),
            new RecipeDescription($command->description),
            ($this->ingredients)($command->ingredients),
        );
        $this->recipes->save($recipe);

        return RecipeView::fromRecipe($recipe);
    }
}
