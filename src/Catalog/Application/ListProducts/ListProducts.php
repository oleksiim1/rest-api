<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListProducts;

final class ListProducts
{
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT = 100;

    public function __construct(
        public readonly int $limit = self::DEFAULT_LIMIT,
        public readonly int $offset = 0,
    ) {
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw new \InvalidArgumentException(\sprintf('Limit must be between 1 and %d.', self::MAX_LIMIT));
        }

        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset must not be negative.');
        }
    }
}
