<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Empties the given tables so each kernel/web test starts from a clean database.
 */
trait TruncatesTables
{
    private static function truncateTables(ContainerInterface $container, string ...$tables): void
    {
        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $em->getConnection()->executeStatement('TRUNCATE TABLE '.implode(', ', $tables));
        $em->clear();
    }
}
