<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Presentation\Cli;

use App\Catalog\Domain\Money;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId as CatalogProductId;
use App\Catalog\Domain\ProductName;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Domain\Unit;
use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;
use App\Recipes\Domain\Ingredient;
use App\Recipes\Domain\ProductId;
use App\Recipes\Domain\Quantity;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeDescription;
use App\Recipes\Domain\RecipeRepository;
use App\Recipes\Domain\RecipeTitle;
use App\Shared\Application\PageRequest;
use App\Tests\ResetsRecipeSearchIndex;
use App\Tests\TruncatesTables;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ReindexRecipesCommandTest extends KernelTestCase
{
    use ResetsRecipeSearchIndex;
    use TruncatesTables;

    private const POTATO = '00000000-0000-7000-8000-000000000001';

    private CommandTester $tester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();
        self::truncateTables($container, 'recipe_ingredients', 'recipes', 'products');
        self::resetRecipeSearchIndex($container);

        $this->tester = new CommandTester((new Application($kernel))->find('app:recipes:reindex'));
    }

    public function testItIndexesTheExistingRecipesAndReportsHowMany(): void
    {
        $products = self::getContainer()->get(ProductRepository::class);
        \assert($products instanceof ProductRepository);
        $products->save(new Product(CatalogProductId::fromString(self::POTATO), new ProductName('Potato'), new Money(2500, 'UAH'), Unit::Kilogram));

        // Saved straight to the repository, so they are not in the index yet.
        $recipes = self::getContainer()->get(RecipeRepository::class);
        \assert($recipes instanceof RecipeRepository);
        foreach (['Potato soup', 'Potato pancakes', 'Borscht'] as $title) {
            $recipes->save(new Recipe(
                $recipes->nextIdentity(),
                new RecipeTitle($title),
                new RecipeDescription('Cook it.'),
                [new Ingredient(ProductId::fromString(self::POTATO), Quantity::fromNumber(1))],
            ));
        }
        self::assertSame([], $this->search('potato'));

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Indexed 3 recipe(s).', $this->tester->getDisplay());
        self::assertSame(['Potato pancakes', 'Potato soup'], array_map(static fn (RecipeView $view): string => $view->title, $this->search('potato')));
    }

    public function testItReportsZeroWithoutRecipes(): void
    {
        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Indexed 0 recipe(s).', $this->tester->getDisplay());
    }

    /**
     * @return list<RecipeView>
     */
    private function search(string $query): array
    {
        $index = self::getContainer()->get(RecipeSearchIndex::class);
        \assert($index instanceof RecipeSearchIndex);

        return $index->search($query, new PageRequest());
    }
}
