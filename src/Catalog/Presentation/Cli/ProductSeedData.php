<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Cli;

/**
 * Real market product names for local development. Faker's default locale has no grocery names,
 * so SeedProductsCommand picks names from here and lets Faker invent the price and unit.
 */
final class ProductSeedData
{
    /** @var list<string> */
    public const NAMES = [
        'Potato', 'Rice', 'Garlic', 'Eggs', 'Onion', 'Carrot', 'Tomato', 'Milk', 'Bread', 'Buckwheat',
        'Apple', 'Cabbage', 'Sunflower oil', 'Sugar', 'Flour', 'Beetroot', 'Cucumber', 'Bell pepper',
        'Zucchini', 'Mushrooms', 'Butter', 'Sour cream', 'Cottage cheese', 'Hard cheese', 'Chicken breast',
        'Pork', 'Beef', 'Salmon', 'Pasta', 'Oats', 'Lentils', 'Beans', 'Salt', 'Black pepper', 'Dill',
        'Parsley', 'Lemon', 'Honey', 'Walnuts', 'Yogurt',
    ];
}
