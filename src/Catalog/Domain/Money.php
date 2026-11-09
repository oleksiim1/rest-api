<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductData;

/**
 * An amount in minor units (kopiyky, cents) with an ISO 4217 currency code.
 */
final class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidProductData('Price amount must not be negative.');
        }

        if (1 !== preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidProductData('Price currency must be a 3-letter uppercase ISO 4217 code.');
        }
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
