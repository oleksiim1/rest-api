<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;
use App\Shared\Application\PageRequest;

/**
 * The test environment's RecipeSearchIndex (see config/services.yaml), so kernel and web tests
 * never need a real Elasticsearch.
 *
 * It keeps one in-memory index for the whole process, because the test client boots a new kernel,
 * and so a new service, for every request. ResetsRecipeSearchIndex empties it before each test.
 */
final class SharedInMemoryRecipeSearchIndex implements RecipeSearchIndex
{
    private static ?InMemoryRecipeSearchIndex $index = null;

    public function index(RecipeView $recipe): void
    {
        self::shared()->index($recipe);
    }

    public function remove(string $id): void
    {
        self::shared()->remove($id);
    }

    public function search(string $query, PageRequest $page): array
    {
        return self::shared()->search($query, $page);
    }

    public function rebuild(iterable $recipes): void
    {
        self::shared()->rebuild($recipes);
    }

    private static function shared(): InMemoryRecipeSearchIndex
    {
        return self::$index ??= new InMemoryRecipeSearchIndex();
    }
}
