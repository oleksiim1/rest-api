<?php

declare(strict_types=1);

namespace App\Recipes\Infrastructure\Search\Elasticsearch;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;
use App\Shared\Application\PageRequest;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;

/**
 * Keeps recipes in an Elasticsearch index. Search results come from each document's _source,
 * so a search does not touch the database.
 *
 * Writes wait for a refresh, so a recipe can be found as soon as the request that saved it returns.
 */
final class ElasticsearchRecipeSearchIndex implements RecipeSearchIndex
{
    private const BULK_BATCH = 500;

    /** Elasticsearch's default index.max_result_window: from + size may not exceed it. */
    private const MAX_RESULT_WINDOW = 10_000;

    private const MAPPING = [
        'dynamic' => 'strict',
        'properties' => [
            'id' => ['type' => 'keyword'],
            'title' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
            'description' => ['type' => 'text'],
            // Stored in _source for the search results, but not searchable.
            'ingredients' => ['type' => 'object', 'enabled' => false],
        ],
    ];

    private bool $exists = false;

    public function __construct(
        private readonly Client $client,
        private readonly string $indexName,
    ) {
    }

    public function index(RecipeView $recipe): void
    {
        $this->ensureIndex();

        $this->client->index([
            'index' => $this->indexName,
            'id' => $recipe->id,
            'body' => $recipe->toArray(),
            'refresh' => 'wait_for',
        ]);
    }

    public function remove(string $id): void
    {
        try {
            $this->client->delete(['index' => $this->indexName, 'id' => $id, 'refresh' => 'wait_for']);
        } catch (ClientResponseException $e) {
            // Not indexed (or no index yet): nothing to remove.
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }
    }

    public function search(string $query, PageRequest $page): array
    {
        // Results past the window cannot be fetched, so that page is empty.
        if ($page->offset >= self::MAX_RESULT_WINDOW) {
            return [];
        }

        try {
            $response = $this->client->search([
                'index' => $this->indexName,
                'body' => [
                    'from' => $page->offset,
                    'size' => min($page->limit, self::MAX_RESULT_WINDOW - $page->offset),
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['title^2', 'description'],
                            'fuzziness' => 'AUTO',
                        ],
                    ],
                    'sort' => ['_score', ['title.keyword' => 'asc'], ['id' => 'asc']],
                ],
            ]);
        } catch (ClientResponseException $e) {
            // The index has not been built yet, so nothing can match.
            if (404 === $e->getCode()) {
                return [];
            }
            throw $e;
        }

        return array_map(
            static fn (array $hit): RecipeView => self::view($hit['_source']),
            self::asArray($response)['hits']['hits'],
        );
    }

    public function rebuild(iterable $recipes): int
    {
        $this->client->indices()->delete(['index' => $this->indexName, 'ignore_unavailable' => true]);
        $this->exists = false;
        $this->ensureIndex();

        $count = 0;
        $operations = [];
        foreach ($recipes as $recipe) {
            ++$count;
            $operations[] = ['index' => ['_index' => $this->indexName, '_id' => $recipe->id]];
            $operations[] = $recipe->toArray();

            if (\count($operations) >= 2 * self::BULK_BATCH) {
                $this->bulk($operations);
                $operations = [];
            }
        }
        if ([] !== $operations) {
            $this->bulk($operations);
        }

        $this->client->indices()->refresh(['index' => $this->indexName]);

        return $count;
    }

    /**
     * Creates the index with its mapping when it is missing, so the first write after a deploy
     * does not leave Elasticsearch to guess the mapping.
     */
    private function ensureIndex(): void
    {
        if ($this->exists) {
            return;
        }

        try {
            $this->client->indices()->create(['index' => $this->indexName, 'body' => ['mappings' => self::MAPPING]]);
        } catch (ClientResponseException $e) {
            if (400 !== $e->getCode() || !str_contains((string) $e->getResponse()->getBody(), 'resource_already_exists_exception')) {
                throw $e;
            }
        }

        $this->exists = true;
    }

    /**
     * @param list<array<string, mixed>> $operations
     */
    private function bulk(array $operations): void
    {
        $response = self::asArray($this->client->bulk(['body' => $operations]));
        if ($response['errors'] ?? false) {
            throw new \RuntimeException(\sprintf('Bulk indexing into "%s" failed for some recipes.', $this->indexName));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function asArray(mixed $response): array
    {
        \assert($response instanceof Elasticsearch);

        return $response->asArray();
    }

    /**
     * @param array{id: string, title: string, description: string, ingredients: list<array{productId: string, quantity: int|float}>} $source
     */
    private static function view(array $source): RecipeView
    {
        return new RecipeView(
            $source['id'],
            $source['title'],
            $source['description'],
            array_map(
                // JSON drops the fraction of a whole number, so 2.0 comes back as 2.
                static fn (array $ingredient): array => ['productId' => $ingredient['productId'], 'quantity' => (float) $ingredient['quantity']],
                $source['ingredients'],
            ),
        );
    }
}
