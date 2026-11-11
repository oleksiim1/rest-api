<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Search\Elasticsearch;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Infrastructure\Search\Elasticsearch\ElasticsearchRecipeSearchIndex;
use App\Tests\Recipes\Application\SharedInMemoryRecipeSearchIndex;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Reads config/services.yaml directly, because the test container binds the in-memory fake
 * and so cannot show the binding the other environments get.
 */
final class RecipeSearchIndexWiringTest extends TestCase
{
    #[DataProvider('environments')]
    public function testThePortIsBoundForTheEnvironment(string $environment, string $implementation): void
    {
        $container = new ContainerBuilder();
        (new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 5).'/config'), $environment))->load('services.yaml');

        self::assertSame($implementation, (string) $container->getAlias(RecipeSearchIndex::class));
    }

    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function environments(): iterable
    {
        yield 'prod' => ['prod', ElasticsearchRecipeSearchIndex::class];
        yield 'dev' => ['dev', ElasticsearchRecipeSearchIndex::class];
        yield 'test' => ['test', SharedInMemoryRecipeSearchIndex::class];
    }
}
