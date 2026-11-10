<?php

declare(strict_types=1);

namespace App\Recipes\Presentation;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\GetRecipe\GetRecipe;
use App\Recipes\Application\GetRecipe\GetRecipeHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipes', name: 'recipes_recipe_')]
final class RecipeController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, CreateRecipeHandler $handler): JsonResponse
    {
        $payload = RecipePayload::fromRequest($request);

        $view = $handler(new CreateRecipe($payload->title, $payload->description, $payload->ingredients));

        return $this->json($view->toArray(), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('recipes_recipe_show', ['id' => $view->id]),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, GetRecipeHandler $handler): JsonResponse
    {
        return $this->json($handler(new GetRecipe($id))->toArray());
    }
}
