<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Presentation;

use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;
use App\Tests\TruncatesTables;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RecipeControllerTest extends WebTestCase
{
    use TruncatesTables;

    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const ONION = '00000000-0000-7000-8000-000000000002';
    private const MISSING_PRODUCT = '00000000-0000-7000-8000-000000000099';
    private const UNKNOWN_ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $container = self::getContainer();
        self::truncateTables($container, 'recipe_ingredients', 'recipes', 'products');

        $products = $container->get(ProductRepository::class);
        \assert($products instanceof ProductRepository);
        $products->save(new Product(ProductId::fromString(self::POTATO), new ProductName('Potato'), new Money(2500, 'UAH'), Unit::Kilogram));
        $products->save(new Product(ProductId::fromString(self::ONION), new ProductName('Onion'), new Money(2200, 'UAH'), Unit::Kilogram));
    }

    public function testCreateReturns201WithLocationAndBody(): void
    {
        $body = $this->request('POST', '/recipes', self::mashedPotatoes());

        self::assertResponseStatusCodeSame(201);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $body['id']);
        self::assertResponseHeaderSame('Location', '/recipes/'.$body['id']);
        self::assertSame([
            'id' => $body['id'],
            'title' => 'Mashed potatoes',
            'description' => 'Boil and mash.',
            'ingredients' => [
                ['productId' => self::POTATO, 'quantity' => 1.5],
                // JSON has one number type; a whole quantity is sent as 1, not 1.0.
                ['productId' => self::ONION, 'quantity' => 1],
            ],
        ], $body);
    }

    public function testCreateRejectsMalformedJsonWith400(): void
    {
        $this->client->request('POST', '/recipes', server: ['CONTENT_TYPE' => 'application/json'], content: '{"title":');

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'Request body is not valid JSON.'], $this->json());
    }

    /**
     * @param array<mixed> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testCreateRejectsInvalidDataWith422(array $payload, string $error): void
    {
        $body = $this->request('POST', '/recipes', $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['error' => $error], $body);
    }

    /** @return iterable<string, array{array<mixed>, string}> */
    public static function invalidPayloads(): iterable
    {
        $recipe = self::mashedPotatoes();
        $potato = ['productId' => self::POTATO, 'quantity' => 1];

        // Shape: InvalidRequestBody.
        yield 'body is a list' => [['Mashed potatoes'], 'Request body must be a JSON object.'];
        yield 'missing title' => [array_diff_key($recipe, ['title' => true]), '"title" must be a string.'];
        yield 'title not a string' => [['title' => 42] + $recipe, '"title" must be a string.'];
        yield 'missing description' => [array_diff_key($recipe, ['description' => true]), '"description" must be a string.'];
        yield 'missing ingredients' => [array_diff_key($recipe, ['ingredients' => true]), '"ingredients" must be a list.'];
        yield 'ingredients an object' => [['ingredients' => ['a' => $potato]] + $recipe, '"ingredients" must be a list.'];
        yield 'ingredient not an object' => [['ingredients' => [self::POTATO]] + $recipe, '"ingredients[0]" must be an object with "productId" and "quantity".'];
        yield 'ingredient without productId' => [['ingredients' => [['quantity' => 1]]] + $recipe, '"ingredients[0].productId" must be a string.'];
        yield 'productId not a string' => [['ingredients' => [['productId' => 1, 'quantity' => 1]]] + $recipe, '"ingredients[0].productId" must be a string.'];
        yield 'ingredient without quantity' => [['ingredients' => [$potato, ['productId' => self::ONION]]] + $recipe, '"ingredients[1].quantity" must be a number.'];
        yield 'quantity as string' => [['ingredients' => [['productId' => self::POTATO, 'quantity' => '1']]] + $recipe, '"ingredients[0].quantity" must be a number.'];

        // Business rules: Domain exceptions.
        yield 'empty title' => [['title' => ' '] + $recipe, 'Recipe title must not be empty.'];
        yield 'title too long' => [['title' => str_repeat('a', 151)] + $recipe, 'Recipe title must be at most 150 characters.'];
        yield 'empty description' => [['description' => ''] + $recipe, 'Recipe description must not be empty.'];
        yield 'no ingredients' => [['ingredients' => []] + $recipe, 'A recipe needs at least one ingredient.'];
        yield 'duplicate product' => [['ingredients' => [$potato, $potato]] + $recipe, \sprintf('Product "%s" appears more than once in the recipe.', self::POTATO)];
        yield 'zero quantity' => [['ingredients' => [['productId' => self::POTATO, 'quantity' => 0]]] + $recipe, 'Ingredient quantity must be positive.'];
        yield 'negative quantity' => [['ingredients' => [['productId' => self::POTATO, 'quantity' => -0.5]]] + $recipe, 'Ingredient quantity must be positive.'];
        yield 'four decimals' => [['ingredients' => [['productId' => self::POTATO, 'quantity' => 0.1234]]] + $recipe, 'Ingredient quantity must have at most 3 decimal places.'];
        yield 'malformed product id' => [['ingredients' => [['productId' => 'potato', 'quantity' => 1]]] + $recipe, '"potato" is not a valid product id.'];
        yield 'unknown product' => [['ingredients' => [['productId' => self::MISSING_PRODUCT, 'quantity' => 1]]] + $recipe, \sprintf('Product "%s" does not exist.', self::MISSING_PRODUCT)];
    }

    public function testShowReturnsTheRecipe(): void
    {
        $created = $this->request('POST', '/recipes', self::mashedPotatoes());

        $body = $this->request('GET', '/recipes/'.$created['id']);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created, $body);
    }

    #[DataProvider('missingIds')]
    public function testShowReturns404ForAnUnknownOrMalformedId(string $id, string $error): void
    {
        $body = $this->request('GET', '/recipes/'.$id);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => $error], $body);
    }

    /** @return iterable<string, array{string, string}> */
    public static function missingIds(): iterable
    {
        yield 'unknown uuid' => [self::UNKNOWN_ID, \sprintf('Recipe "%s" was not found.', self::UNKNOWN_ID)];
        yield 'not a uuid' => ['42', '"42" is not a valid recipe id.'];
    }

    public function testListReturnsRecipesOrderedByTitle(): void
    {
        $this->request('POST', '/recipes', ['title' => 'Varenyky'] + self::mashedPotatoes());
        $created = $this->request('POST', '/recipes', ['title' => 'Borscht'] + self::mashedPotatoes());

        $body = $this->request('GET', '/recipes');

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['Borscht', 'Varenyky'], array_column($body, 'title'));
        self::assertSame($created, $body[0]);
    }

    public function testListIsAnEmptyArrayWithoutRecipes(): void
    {
        self::assertSame([], $this->request('GET', '/recipes'));
        self::assertResponseStatusCodeSame(200);
    }

    public function testListReturnsTheRequestedPage(): void
    {
        foreach (['Varenyky', 'Borscht', 'Holubtsi', 'Deruny'] as $title) {
            $this->request('POST', '/recipes', ['title' => $title] + self::mashedPotatoes());
        }

        $body = $this->request('GET', '/recipes?limit=2&offset=1');

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['Deruny', 'Holubtsi'], array_column($body, 'title'));
    }

    #[DataProvider('invalidPages')]
    public function testListRejectsAnInvalidPageWith400(string $query): void
    {
        $body = $this->request('GET', '/recipes?'.$query);

        self::assertResponseStatusCodeSame(400);
        self::assertIsString($body['error']);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPages(): iterable
    {
        yield 'limit zero' => ['limit=0'];
        yield 'limit above max' => ['limit=101'];
        yield 'limit not a number' => ['limit=all'];
        yield 'negative offset' => ['offset=-5'];
    }

    public function testUpdateReplacesTheRecipe(): void
    {
        $created = $this->request('POST', '/recipes', self::mashedPotatoes());

        $body = $this->request('PUT', '/recipes/'.$created['id'], [
            'title' => 'Onion soup',
            'description' => 'Fry the onion, then simmer.',
            'ingredients' => [
                ['productId' => self::ONION, 'quantity' => 0.75],
                ['productId' => self::POTATO, 'quantity' => 0.3],
            ],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame([
            'id' => $created['id'],
            'title' => 'Onion soup',
            'description' => 'Fry the onion, then simmer.',
            'ingredients' => [
                ['productId' => self::ONION, 'quantity' => 0.75],
                ['productId' => self::POTATO, 'quantity' => 0.3],
            ],
        ], $body);
        self::assertSame($body, $this->request('GET', '/recipes/'.$created['id']));
    }

    public function testUpdateReturns404ForAnUnknownRecipe(): void
    {
        $this->request('PUT', '/recipes/'.self::UNKNOWN_ID, self::mashedPotatoes());

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<mixed> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testUpdateRejectsInvalidDataWith422AndKeepsTheRecipe(array $payload, string $error): void
    {
        $created = $this->request('POST', '/recipes', self::mashedPotatoes());

        $body = $this->request('PUT', '/recipes/'.$created['id'], $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['error' => $error], $body);
        self::assertSame($created, $this->request('GET', '/recipes/'.$created['id']));
    }

    public function testUpdateReturns400ForMalformedJson(): void
    {
        $created = $this->request('POST', '/recipes', self::mashedPotatoes());

        $this->client->request('PUT', '/recipes/'.$created['id'], server: ['CONTENT_TYPE' => 'application/json'], content: 'nope');

        self::assertResponseStatusCodeSame(400);
    }

    public function testDeleteReturns204AndRemovesTheRecipe(): void
    {
        $created = $this->request('POST', '/recipes', self::mashedPotatoes());

        $this->client->request('DELETE', '/recipes/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', (string) $this->client->getResponse()->getContent());

        $this->request('GET', '/recipes/'.$created['id']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteReturns404ForAnUnknownRecipe(): void
    {
        $this->client->request('DELETE', '/recipes/'.self::UNKNOWN_ID);

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return array{title: string, description: string, ingredients: list<array{productId: string, quantity: int|float}>}
     */
    private static function mashedPotatoes(): array
    {
        return [
            'title' => 'Mashed potatoes',
            'description' => 'Boil and mash.',
            'ingredients' => [
                ['productId' => self::POTATO, 'quantity' => 1.5],
                ['productId' => self::ONION, 'quantity' => 1],
            ],
        ];
    }

    /**
     * @param array<mixed>|null $payload
     *
     * @return array<mixed>
     */
    private function request(string $method, string $uri, ?array $payload = null): array
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: null === $payload ? null : json_encode($payload, \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION),
        );

        return $this->json();
    }

    /** @return array<mixed> */
    private function json(): array
    {
        $content = (string) $this->client->getResponse()->getContent();

        return '' === $content ? [] : json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
    }
}
