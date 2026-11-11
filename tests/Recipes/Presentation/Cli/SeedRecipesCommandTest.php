<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Presentation\Cli;

use App\Catalog\Domain\ProductRepository;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeRepository;
use App\Tests\TruncatesTables;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
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

    public function testItCreatesTenRecipesByDefault(): void
    {
        $this->seedProducts();

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertCount(10, $this->recipes());
    }

    #[DataProvider('invalidCounts')]
    public function testItRejectsACountThatIsNotAPositiveInteger(string $count): void
    {
        $this->seedProducts();

        $status = $this->tester->execute(['--count' => $count]);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString('--count must be a positive integer', $this->tester->getDisplay());
        self::assertCount(0, $this->recipes());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCounts(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-2'];
        yield 'decimal' => ['1.5'];
        yield 'word' => ['three'];
        yield 'empty' => [''];
    }

    public function testItFailsWhenTheCatalogHasNoProducts(): void
    {
        $status = $this->tester->execute(['--count' => '3']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('app:catalog:seed-products', $this->tester->getDisplay());
        self::assertCount(0, $this->recipes());
    }

    public function testTheSameSeedProducesTheSameTitles(): void
    {
        $this->seedProducts();

        $this->tester->execute(['--count' => '3', '--seed' => '42']);
        $first = $this->titles();
        self::truncateTables(self::getContainer(), 'recipe_ingredients', 'recipes');
        $this->tester->execute(['--count' => '3', '--seed' => '42']);

        self::assertCount(3, $first);
        self::assertSame($first, $this->titles());
    }

    /**
     * @return list<string> sorted
     */
    private function titles(): array
    {
        $titles = array_map(static fn (Recipe $recipe): string => $recipe->title()->value(), $this->recipes());
        sort($titles);

        return $titles;
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
