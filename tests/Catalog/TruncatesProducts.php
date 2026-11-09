<?php

declare(strict_types=1);

namespace App\Tests\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Empties the products table so each kernel/web test starts from a clean database.
 */
trait TruncatesProducts
{
    private static function truncateProducts(ContainerInterface $container): void
    {
        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $em->getConnection()->executeStatement('TRUNCATE TABLE products');
        $em->clear();
    }
}
