<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation;

use App\Tests\TruncatesTables;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductControllerTest extends WebTestCase
{
    use TruncatesTables;

    private const UNKNOWN_ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        self::truncateTables(self::getContainer(), 'products');
    }

    public function testCreateReturns201WithLocationAndBody(): void
    {
        $body = $this->request('POST', '/products', self::potato());

        self::assertResponseStatusCodeSame(201);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $body['id']);
        self::assertResponseHeaderSame('Location', '/products/'.$body['id']);
        self::assertSame([
            'id' => $body['id'],
            'name' => 'Potato',
            'price' => ['amount' => 2500, 'currency' => 'UAH'],
            'unit' => 'kg',
        ], $body);
    }

    public function testCreateRejectsMalformedJsonWith400(): void
    {
        $this->client->request('POST', '/products', server: ['CONTENT_TYPE' => 'application/json'], content: '{"name":');

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'Request body is not valid JSON.'], $this->json());
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testCreateRejectsInvalidDataWith422(array $payload): void
    {
        $body = $this->request('POST', '/products', $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertIsString($body['error']);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidPayloads(): iterable
    {
        $potato = self::potato();

        yield 'missing name' => [array_diff_key($potato, ['name' => true])];
        yield 'missing price' => [array_diff_key($potato, ['price' => true])];
        yield 'missing unit' => [array_diff_key($potato, ['unit' => true])];
        yield 'name not a string' => [['name' => 42] + $potato];
        yield 'empty name' => [['name' => ' '] + $potato];
        yield 'price not an object' => [['price' => 2500] + $potato];
        yield 'price without amount' => [['price' => ['currency' => 'UAH']] + $potato];
        yield 'price without currency' => [['price' => ['amount' => 2500]] + $potato];
        yield 'amount as float' => [['price' => ['amount' => 25.5, 'currency' => 'UAH']] + $potato];
        yield 'amount as string' => [['price' => ['amount' => '2500', 'currency' => 'UAH']] + $potato];
        yield 'negative amount' => [['price' => ['amount' => -1, 'currency' => 'UAH']] + $potato];
        yield 'amount too large to store' => [['price' => ['amount' => 2147483648, 'currency' => 'UAH']] + $potato];
        yield 'amount beyond 64 bits' => [['price' => ['amount' => 3e20, 'currency' => 'UAH']] + $potato];
        yield 'currency not a string' => [['price' => ['amount' => 2500, 'currency' => 980]] + $potato];
        yield 'bad currency' => [['price' => ['amount' => 2500, 'currency' => 'uah']] + $potato];
        yield 'unit not a string' => [['unit' => 1] + $potato];
        yield 'unknown unit' => [['unit' => 'bucket'] + $potato];
        yield 'body is a list' => [['Potato', 2500]];
    }

    public function testCreateAcceptsTheLargestStorableAmount(): void
    {
        $body = $this->request('POST', '/products', ['price' => ['amount' => 2147483647, 'currency' => 'UAH']] + self::potato());

        self::assertResponseStatusCodeSame(201);
        self::assertSame(2147483647, $body['price']['amount']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testUpdateRejectsInvalidDataWith422(array $payload): void
    {
        $created = $this->request('POST', '/products', ['name' => 'Rice'] + self::potato());

        $body = $this->request('PUT', '/products/'.$created['id'], $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertIsString($body['error']);
    }

    public function testCreateRejectsDuplicateNameWith409(): void
    {
        $this->request('POST', '/products', self::potato());
        $body = $this->request('POST', '/products', ['name' => 'POTATO'] + self::potato());

        self::assertResponseStatusCodeSame(409);
        self::assertSame('A product named "POTATO" already exists.', $body['error']);
    }

    public function testListReturnsProductsOrderedByName(): void
    {
        $this->request('POST', '/products', self::potato());
        $this->request('POST', '/products', ['name' => 'Eggs', 'price' => ['amount' => 7000, 'currency' => 'UAH'], 'unit' => 'dozen']);

        $body = $this->request('GET', '/products');

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['Eggs', 'Potato'], array_column($body, 'name'));
    }

    public function testListReturnsTheRequestedPage(): void
    {
        foreach (['Rice', 'Eggs', 'Garlic', 'Potato'] as $name) {
            $this->request('POST', '/products', ['name' => $name] + self::potato());
        }

        $body = $this->request('GET', '/products?limit=2&offset=1');

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['Garlic', 'Potato'], array_column($body, 'name'));
    }

    #[DataProvider('invalidPages')]
    public function testListRejectsAnInvalidPageWith400(string $query): void
    {
        $body = $this->request('GET', '/products?'.$query);

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

    public function testListIsAnEmptyArrayWithoutProducts(): void
    {
        self::assertSame([], $this->request('GET', '/products'));
        self::assertResponseStatusCodeSame(200);
    }

    public function testShowReturnsTheProduct(): void
    {
        $created = $this->request('POST', '/products', self::potato());

        $body = $this->request('GET', '/products/'.$created['id']);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($created, $body);
    }

    #[DataProvider('missingIds')]
    public function testShowReturns404ForAnUnknownOrMalformedId(string $id): void
    {
        $body = $this->request('GET', '/products/'.$id);

        self::assertResponseStatusCodeSame(404);
        self::assertIsString($body['error']);
    }

    /** @return iterable<string, array{string}> */
    public static function missingIds(): iterable
    {
        yield 'unknown uuid' => [self::UNKNOWN_ID];
        yield 'not a uuid' => ['42'];
    }

    public function testUpdateReplacesTheProduct(): void
    {
        $created = $this->request('POST', '/products', self::potato());

        $body = $this->request('PUT', '/products/'.$created['id'], [
            'name' => 'Young potato',
            'price' => ['amount' => 4000, 'currency' => 'UAH'],
            'unit' => 'kg',
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertSame('Young potato', $body['name']);
        self::assertSame(4000, $body['price']['amount']);
        self::assertSame($body, $this->request('GET', '/products/'.$created['id']));
    }

    public function testUpdateReturns404ForAnUnknownProduct(): void
    {
        $this->request('PUT', '/products/'.self::UNKNOWN_ID, self::potato());

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdateReturns422ForInvalidData(): void
    {
        $created = $this->request('POST', '/products', self::potato());

        $this->request('PUT', '/products/'.$created['id'], ['unit' => 'bucket'] + self::potato());

        self::assertResponseStatusCodeSame(422);
    }

    public function testUpdateReturns409WhenTheNameBelongsToAnotherProduct(): void
    {
        $this->request('POST', '/products', ['name' => 'Rice'] + self::potato());
        $created = $this->request('POST', '/products', self::potato());

        $this->request('PUT', '/products/'.$created['id'], ['name' => 'rice'] + self::potato());

        self::assertResponseStatusCodeSame(409);
    }

    public function testUpdateReturns400ForMalformedJson(): void
    {
        $created = $this->request('POST', '/products', self::potato());

        $this->client->request('PUT', '/products/'.$created['id'], server: ['CONTENT_TYPE' => 'application/json'], content: 'nope');

        self::assertResponseStatusCodeSame(400);
    }

    public function testDeleteReturns204AndRemovesTheProduct(): void
    {
        $created = $this->request('POST', '/products', self::potato());

        $this->client->request('DELETE', '/products/'.$created['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', (string) $this->client->getResponse()->getContent());

        $this->request('GET', '/products/'.$created['id']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteReturns404ForAnUnknownProduct(): void
    {
        $this->client->request('DELETE', '/products/'.self::UNKNOWN_ID);

        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownRouteReturnsJson404(): void
    {
        $body = $this->request('GET', '/nope');

        self::assertResponseStatusCodeSame(404);
        self::assertIsString($body['error']);
    }

    public function testWrongMethodReturnsJson405(): void
    {
        $body = $this->request('PATCH', '/products');

        self::assertResponseStatusCodeSame(405);
        self::assertIsString($body['error']);
    }

    /** @return array{name: string, price: array{amount: int, currency: string}, unit: string} */
    private static function potato(): array
    {
        return ['name' => 'Potato', 'price' => ['amount' => 2500, 'currency' => 'UAH'], 'unit' => 'kg'];
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
            content: null === $payload ? null : json_encode($payload, \JSON_THROW_ON_ERROR),
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
