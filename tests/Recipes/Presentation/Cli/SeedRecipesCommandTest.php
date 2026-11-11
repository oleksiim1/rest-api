<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Presentation\Cli;

use App\Catalog\Domain\ProductRepository;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeRepository;
use App\Tests\TruncatesTables;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedRecipesCommandTest extends KernelTestCase
{
    use TruncatesTables;

    private Application $application;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        self::truncateTables(self::getContainer(), 'recipe_ingredients', 'recipes', 'products');

        $this->application = new Application($kernel);
        $this->tester = new CommandTester($this->application->find('app:recipes:seed-recipes'));
    }

    public function testItCreatesTheRequestedNumberOfRecipesFromExistingProducts(): void
    {
        $this->seedProducts();

        $this->tester->execute(['--count' => '3']);

        $this->tester->assertCommandIsSuccessful();
        $recipes = $this->recipes();
        self::assertCount(3, $recipes);

        $productIds = array_map(
            static fn ($product): string => $product->id()->toString(),
            self::getContainer()->get(ProductRepository::class)->page(0, 1000),
        );

        foreach ($recipes as $recipe) {
            $ingredients = $recipe->ingredients();
            self::assertGreaterThanOrEqual(2, \count($ingredients));
            self::assertLessThanOrEqual(6, \count($ingredients));

            $ids = array_map(static fn ($ingredient): string => $ingredient->productId()->toString(), $ingredients);
            self::assertSame($ids, array_values(array_unique($ids)), 'Ingredients must be distinct products.');

            foreach ($ingredients as $ingredient) {
                self::assertContains($ingredient->productId()->toString(), $productIds);
                self::assertGreaterThan(0, $ingredient->quantity()->toFloat());
            }
        }
    }

    private function seedProducts(): void
    {
        (new CommandTester($this->application->find('app:catalog:seed-products')))->execute([]);
    }

    /**
     * @return list<Recipe>
     */
    private function recipes(): array
    {
        return self::getContainer()->get(RecipeRepository::class)->page(0, 1000);
    }
}
