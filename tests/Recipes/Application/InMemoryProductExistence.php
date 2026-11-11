<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\ProductExistence;
use App\Recipes\Domain\ProductId;

final class InMemoryProductExistence implements ProductExistence
{
    /** @var array<string, true> */
    private array $ids = [];

    public function __construct(string ...$ids)
    {
        foreach ($ids as $id) {
            $this->ids[ProductId::fromString($id)->toString()] = true;
        }
    }

    public function exists(ProductId $id): bool
    {
        return isset($this->ids[$id->toString()]);
    }
}
