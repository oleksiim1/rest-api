<?php

declare(strict_types=1);

namespace App\Tests;

use App\Recipes\Application\RecipeSearchIndex;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rebuilds an empty recipe search index so each kernel/web test starts without indexed recipes.
 */
trait ResetsRecipeSearchIndex
{
    private static function resetRecipeSearchIndex(ContainerInterface $container): void
    {
        $index = $container->get(RecipeSearchIndex::class);
        \assert($index instanceof RecipeSearchIndex);

        $index->rebuild([]);
    }
}
