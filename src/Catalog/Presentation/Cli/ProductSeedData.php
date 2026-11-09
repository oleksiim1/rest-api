<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Cli;

/**
 * Sample market products for local development. Prices are in kopiyky (minor units of UAH).
 */
final class ProductSeedData
{
    /** @var list<array{name: string, amount: int, currency: string, unit: string}> */
    public const PRODUCTS = [
        ['name' => 'Potato', 'amount' => 2500, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Rice', 'amount' => 6500, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Garlic', 'amount' => 18000, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Eggs', 'amount' => 7000, 'currency' => 'UAH', 'unit' => 'dozen'],
        ['name' => 'Onion', 'amount' => 2200, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Carrot', 'amount' => 2000, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Tomato', 'amount' => 9000, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Milk', 'amount' => 4500, 'currency' => 'UAH', 'unit' => 'l'],
        ['name' => 'Bread', 'amount' => 3500, 'currency' => 'UAH', 'unit' => 'piece'],
        ['name' => 'Buckwheat', 'amount' => 6000, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Apple', 'amount' => 4000, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Cabbage', 'amount' => 1800, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Sunflower oil', 'amount' => 9500, 'currency' => 'UAH', 'unit' => 'l'],
        ['name' => 'Sugar', 'amount' => 4200, 'currency' => 'UAH', 'unit' => 'kg'],
        ['name' => 'Flour', 'amount' => 3000, 'currency' => 'UAH', 'unit' => 'kg'],
    ];
}
