<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use App\Catalog\Domain\Exception\InvalidProductData;

/**
 * The quantity a product's price refers to, e.g. potatoes per kg, eggs per dozen.
 */
enum Unit: string
{
    case Kilogram = 'kg';
    case Gram = 'g';
    case Liter = 'l';
    case Milliliter = 'ml';
    case Piece = 'piece';
    case Dozen = 'dozen';
    case Pack = 'pack';

    public static function fromCode(string $code): self
    {
        return self::tryFrom($code) ?? throw new InvalidProductData(\sprintf(
            'Unknown unit "%s". Allowed: %s.',
            $code,
            implode(', ', array_map(static fn (self $unit): string => $unit->value, self::cases())),
        ));
    }
}
