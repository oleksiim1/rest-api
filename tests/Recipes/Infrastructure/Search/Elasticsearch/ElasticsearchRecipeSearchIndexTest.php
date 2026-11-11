<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Search\Elasticsearch;

use App\Recipes\Application\RecipeView;
use App\Recipes\Infrastructure\Search\Elasticsearch\ElasticsearchRecipeSearchIndex;
use App\Shared\Application\PageRequest;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Checks the requests the adapter sends and how it maps the responses, without a real Elasticsearch.
 * ElasticsearchRecipeSearchIndexIntegrationTest checks the actual search behaviour.
 */
final class ElasticsearchRecipeSearchIndexTest extends TestCase
{
    private const INDEX = 'recipes_unit';
    private const ID = '00000000-0000-7000-9000-000000000001';

    /** @var list<array{method: string, url: string, body: mixed}> */
    private array $requests = [];

    /** @var list<MockResponse> */
    private array $responses = [];

    public function testSearchSendsAFuzzyMultiMatchQueryWithTheTitleBoosted(): void
    {
        $this->respond(['hits' => ['hits' => []]]);

        $this->index()->search('borscht', new PageRequest(limit: 20, offset: 40));

        self::assertSame('POST', $this->requests[0]['method']);
        self::assertSame('http://es.test:9200/'.self::INDEX.'/_search', $this->requests[0]['url']);
        self::assertSame([
            'from' => 40,
            'size' => 20,
            'query' => [
                'multi_match' => [
                    'query' => 'borscht',
                    'fields' => ['title^2', 'description'],
                    'fuzziness' => 'AUTO',
                ],
            ],
            'sort' => ['_score', ['title.keyword' => 'asc'], ['id' => 'asc']],
        ], $this->requests[0]['body']);
    }

    public function testSearchMapsEachHitSourceToARecipeView(): void
    {
        $this->respond(['hits' => ['hits' => [
            ['_id' => self::ID, '_score' => 1.2, '_source' => [
                'id' => self::ID,
                'title' => 'Ukrainian borscht',
                'description' => 'Simmer beets with cabbage.',
                // A whole quantity comes back from JSON as an int.
                'ingredients' => [['productId' => '00000000-0000-7000-8000-000000000001', 'quantity' => 2]],
            ]],
        ]]]);

        $views = $this->index()->search('borscht', new PageRequest());

        self::assertEquals([new RecipeView(self::ID, 'Ukrainian borscht', 'Simmer beets with cabbage.', [
            ['productId' => '00000000-0000-7000-8000-000000000001', 'quantity' => 2.0],
        ])], $views);
        self::assertIsFloat($views[0]->ingredients[0]['quantity']);
    }

    public function testSearchFindsNothingBeforeTheIndexExists(): void
    {
        $this->respond(['error' => ['type' => 'index_not_found_exception']], 404);

        self::assertSame([], $this->index()->search('borscht', new PageRequest()));
    }

    public function testSearchRethrowsOtherErrors(): void
    {
        $this->respond(['error' => ['type' => 'search_phase_execution_exception']], 400);

        $this->expectException(ClientResponseException::class);

        $this->index()->search('borscht', new PageRequest());
    }

    public function testSearchCapsThePageAtTheResultWindow(): void
    {
        $this->respond(['hits' => ['hits' => []]]);

        $this->index()->search('borscht', new PageRequest(limit: 100, offset: 9950));

        self::assertSame(50, $this->requests[0]['body']['size']);
    }

    public function testSearchPastTheResultWindowSendsNoRequest(): void
    {
        self::assertSame([], $this->index()->search('borscht', new PageRequest(limit: 1, offset: 10_000)));
        self::assertSame([], $this->requests);
    }

    public function testIndexCreatesTheIndexOnceThenWritesTheDocumentAndWaitsForARefresh(): void
    {
        $this->respond(['acknowledged' => true]);
        $this->respond(['result' => 'created']);
        $this->respond(['result' => 'updated']);
        $index = $this->index();

        $index->index(self::view('Ukrainian borscht'));
        $index->index(self::view('Green borscht'));

        self::assertSame(['PUT', 'http://es.test:9200/'.self::INDEX], [$this->requests[0]['method'], $this->requests[0]['url']]);
        self::assertSame(self::mappings(), $this->requests[0]['body']);

        self::assertSame('PUT', $this->requests[1]['method']);
        self::assertSame('http://es.test:9200/'.self::INDEX.'/_doc/'.self::ID.'?refresh=wait_for', $this->requests[1]['url']);
        self::assertSame(self::view('Ukrainian borscht')->toArray(), $this->requests[1]['body']);

        self::assertCount(3, $this->requests);
        self::assertSame(['title' => 'Green borscht'], array_intersect_key($this->requests[2]['body'], ['title' => true]));
    }

    public function testIndexToleratesAnIndexThatAlreadyExists(): void
    {
        $this->respond(['error' => ['type' => 'resource_already_exists_exception']], 400);
        $this->respond(['result' => 'created']);

        $this->index()->index(self::view('Ukrainian borscht'));

        self::assertCount(2, $this->requests);
    }

    public function testRemoveDeletesTheDocumentAndWaitsForARefresh(): void
    {
        $this->respond(['result' => 'deleted']);

        $this->index()->remove(self::ID);

        self::assertSame('DELETE', $this->requests[0]['method']);
        self::assertSame('http://es.test:9200/'.self::INDEX.'/_doc/'.self::ID.'?refresh=wait_for', $this->requests[0]['url']);
    }

    public function testRemoveIgnoresAMissingDocument(): void
    {
        $this->respond(['result' => 'not_found'], 404);

        $this->index()->remove(self::ID);

        self::assertCount(1, $this->requests);
    }

    public function testRemoveRethrowsOtherErrors(): void
    {
        $this->respond(['error' => ['type' => 'cluster_block_exception']], 403);

        $this->expectException(ClientResponseException::class);

        $this->index()->remove(self::ID);
    }

    public function testRebuildRecreatesTheIndexAndBulkLoadsTheRecipesInBatches(): void
    {
        $this->respond(['acknowledged' => true]);
        $this->respond(['acknowledged' => true]);
        $this->respond(['errors' => false, 'items' => []]);
        $this->respond(['errors' => false, 'items' => []]);
        $this->respond(['_shards' => []]);

        $this->index()->rebuild((static function (): \Generator {
            for ($i = 1; $i <= 501; ++$i) {
                yield new RecipeView(\sprintf('00000000-0000-7000-9000-%012d', $i), 'Recipe '.$i, 'Cook it.', []);
            }
        })());

        self::assertSame(
            [
                ['DELETE', 'http://es.test:9200/'.self::INDEX.'?ignore_unavailable=true'],
                ['PUT', 'http://es.test:9200/'.self::INDEX],
                ['POST', 'http://es.test:9200/_bulk'],
                ['POST', 'http://es.test:9200/_bulk'],
                ['POST', 'http://es.test:9200/'.self::INDEX.'/_refresh'],
            ],
            array_map(static fn (array $request): array => [$request['method'], $request['url']], $this->requests),
        );
        self::assertSame(self::mappings(), $this->requests[1]['body']);

        $first = self::ndjson($this->requests[2]['body']);
        self::assertCount(1000, $first);
        self::assertSame(['index' => ['_index' => self::INDEX, '_id' => '00000000-0000-7000-9000-000000000001']], $first[0]);
        self::assertSame(['id' => '00000000-0000-7000-9000-000000000001', 'title' => 'Recipe 1', 'description' => 'Cook it.', 'ingredients' => []], $first[1]);
        self::assertCount(2, self::ndjson($this->requests[3]['body']));
    }

    public function testRebuildWithNoRecipesSendsNoBulkRequest(): void
    {
        $this->respond(['acknowledged' => true]);
        $this->respond(['acknowledged' => true]);
        $this->respond(['_shards' => []]);

        $this->index()->rebuild([]);

        self::assertNotContains('http://es.test:9200/_bulk', array_column($this->requests, 'url'));
        self::assertCount(3, $this->requests);
    }

    public function testRebuildFailsWhenABulkItemFails(): void
    {
        $this->respond(['acknowledged' => true]);
        $this->respond(['acknowledged' => true]);
        $this->respond(['errors' => true, 'items' => []]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bulk indexing into "recipes_unit" failed for some recipes.');

        $this->index()->rebuild([self::view('Ukrainian borscht')]);
    }

    private function index(): ElasticsearchRecipeSearchIndex
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $body = $options['body'] ?? '';
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'body' => \is_string($body) && str_starts_with($body, '{') && !str_contains($body, "\n")
                    ? json_decode($body, true, flags: \JSON_THROW_ON_ERROR)
                    : $body,
            ];

            return array_shift($this->responses) ?? throw new \LogicException(\sprintf('Unexpected request: %s %s', $method, $url));
        });

        $client = ClientBuilder::create()
            ->setHosts(['http://es.test:9200'])
            ->setHttpClient(new Psr18Client($http))
            ->build();

        return new ElasticsearchRecipeSearchIndex($client, self::INDEX);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function respond(array $body, int $status = 200): void
    {
        $this->responses[] = new MockResponse(json_encode($body, \JSON_THROW_ON_ERROR), [
            'http_code' => $status,
            'response_headers' => ['Content-Type' => 'application/json', 'X-Elastic-Product' => 'Elasticsearch'],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function ndjson(mixed $body): array
    {
        \assert(\is_string($body));

        return array_map(
            static fn (string $line): array => json_decode($line, true, flags: \JSON_THROW_ON_ERROR),
            array_values(array_filter(explode("\n", $body), static fn (string $line): bool => '' !== $line)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function mappings(): array
    {
        return ['mappings' => [
            'dynamic' => 'strict',
            'properties' => [
                'id' => ['type' => 'keyword'],
                'title' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                'description' => ['type' => 'text'],
                'ingredients' => ['type' => 'object', 'enabled' => false],
            ],
        ]];
    }

    private static function view(string $title): RecipeView
    {
        return new RecipeView(self::ID, $title, 'Simmer beets with cabbage.', [
            ['productId' => '00000000-0000-7000-8000-000000000001', 'quantity' => 1.5],
        ]);
    }
}
