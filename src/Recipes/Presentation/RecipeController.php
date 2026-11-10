<?php

declare(strict_types=1);

namespace App\Recipes\Presentation;

use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\DeleteRecipe\DeleteRecipe;
use App\Recipes\Application\DeleteRecipe\DeleteRecipeHandler;
use App\Recipes\Application\GetRecipe\GetRecipe;
use App\Recipes\Application\GetRecipe\GetRecipeHandler;
use App\Recipes\Application\ListRecipes\ListRecipes;
use App\Recipes\Application\ListRecipes\ListRecipesHandler;
use App\Recipes\Application\RecipeView;
use App\Recipes\Application\UpdateRecipe\UpdateRecipe;
use App\Recipes\Application\UpdateRecipe\UpdateRecipeHandler;
use App\Shared\Presentation\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipes', name: 'recipes_recipe_')]
final class RecipeController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, ListRecipesHandler $handler): JsonResponse
    {
        $page = Pagination::fromRequest($request, ListRecipes::DEFAULT_LIMIT, ListRecipes::MAX_LIMIT);

        return $this->json(array_map(
            static fn (RecipeView $view): array => $view->toArray(),
            $handler(new ListRecipes($page->limit, $page->offset)),
        ));
    }

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

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateRecipeHandler $handler): JsonResponse
    {
        $payload = RecipePayload::fromRequest($request);

        $view = $handler(new UpdateRecipe($id, $payload->title, $payload->description, $payload->ingredients));

        return $this->json($view->toArray());
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteRecipeHandler $handler): Response
    {
        $handler(new DeleteRecipe($id));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
