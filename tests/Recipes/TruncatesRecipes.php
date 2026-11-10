<?php

declare(strict_types=1);

namespace App\Tests\Recipes;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Empties the recipe tables so each kernel/web test starts from a clean database.
 */
trait TruncatesRecipes
{
    private static function truncateRecipes(ContainerInterface $container): void
    {
        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $em->getConnection()->executeStatement('TRUNCATE TABLE recipe_ingredients, recipes');
        $em->clear();
    }
}
