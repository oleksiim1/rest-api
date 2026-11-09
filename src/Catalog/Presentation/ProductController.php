<?php

declare(strict_types=1);

namespace App\Catalog\Presentation;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Application\DeleteProduct\DeleteProduct;
use App\Catalog\Application\DeleteProduct\DeleteProductHandler;
use App\Catalog\Application\GetProduct\GetProduct;
use App\Catalog\Application\GetProduct\GetProductHandler;
use App\Catalog\Application\ListProducts\ListProducts;
use App\Catalog\Application\ListProducts\ListProductsHandler;
use App\Catalog\Application\ProductView;
use App\Catalog\Application\UpdateProduct\UpdateProduct;
use App\Catalog\Application\UpdateProduct\UpdateProductHandler;
use App\Shared\Presentation\Pagination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'catalog_product_')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, ListProductsHandler $handler): JsonResponse
    {
        $page = Pagination::fromRequest($request, ListProducts::DEFAULT_LIMIT, ListProducts::MAX_LIMIT);

        return $this->json(array_map(
            static fn (ProductView $view): array => $view->toArray(),
            $handler(new ListProducts($page->limit, $page->offset)),
        ));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, CreateProductHandler $handler): JsonResponse
    {
        $payload = ProductPayload::fromRequest($request);

        $view = $handler(new CreateProduct($payload->name, $payload->priceAmount, $payload->priceCurrency, $payload->unit));

        return $this->json($view->toArray(), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('catalog_product_show', ['id' => $view->id]),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, GetProductHandler $handler): JsonResponse
    {
        return $this->json($handler(new GetProduct($id))->toArray());
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateProductHandler $handler): JsonResponse
    {
        $payload = ProductPayload::fromRequest($request);

        $view = $handler(new UpdateProduct($id, $payload->name, $payload->priceAmount, $payload->priceCurrency, $payload->unit));

        return $this->json($view->toArray());
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteProductHandler $handler): Response
    {
        $handler(new DeleteProduct($id));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
