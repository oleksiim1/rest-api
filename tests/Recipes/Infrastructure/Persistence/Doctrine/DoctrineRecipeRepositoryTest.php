<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Persistence\Doctrine;

use App\Recipes\Domain\Ingredient;
use App\Recipes\Domain\ProductId;
use App\Recipes\Domain\Quantity;
use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeDescription;
use App\Recipes\Domain\RecipeRepository;
use App\Recipes\Domain\RecipeTitle;
use App\Recipes\Infrastructure\Persistence\Doctrine\DoctrineRecipeRepository;
use App\Tests\TruncatesTables;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineRecipeRepositoryTest extends KernelTestCase
{
    use TruncatesTables;

    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const ONION = '00000000-0000-7000-8000-000000000002';
    private const BEET = '00000000-0000-7000-8000-000000000003';

    private RecipeRepository $recipes;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::truncateTables($container, 'recipe_ingredients', 'recipes');

        $this->recipes = $container->get(RecipeRepository::class);
        $this->em = $container->get(EntityManagerInterface::class);
    }

    public function testTheInterfaceIsBoundToTheDoctrineImplementation(): void
    {
        self::assertInstanceOf(DoctrineRecipeRepository::class, $this->recipes);
    }

    public function testNextIdentityIsAUuidV7(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $this->recipes->nextIdentity()->toString(),
        );
    }

    public function testItRoundTripsARecipeWithItsIngredientsInOrder(): void
    {
        $recipe = $this->newRecipe('Borscht', [[self::ONION, 0.2], [self::BEET, 0.75], [self::POTATO, 1]]);
        $this->em->clear();

        $loaded = $this->recipes->ofId($recipe->id());

        self::assertNotNull($loaded);
        self::assertNotSame($recipe, $loaded);
        self::assertTrue($loaded->id()->equals($recipe->id()));
        self::assertSame('Borscht', $loaded->title()->value());
        self::assertSame('Simmer for an hour.', $loaded->description()->value());
        self::assertSame(
            [[self::ONION, '0.200'], [self::BEET, '0.750'], [self::POTATO, '1.000']],
            self::flatten($loaded->ingredients()),
        );
    }

    /**
     * Keeping a product across the update matters: its new row has the same (recipe_id,
     * product_id) as the old one, so the old rows must be gone before the new ones are inserted.
     */
    public function testUpdatingReplacesTheIngredientRows(): void
    {
        $recipe = $this->newRecipe('Borscht', [[self::POTATO, 1], [self::ONION, 0.2]]);
        $this->em->clear();

        $loaded = $this->recipes->ofId($recipe->id());
        self::assertNotNull($loaded);
        $loaded->update(
            new RecipeTitle('Beet soup'),
            new RecipeDescription('Simmer.'),
            self::ingredients([[self::BEET, 0.5], [self::POTATO, 2]]),
        );
        $this->recipes->save($loaded);
        $this->em->clear();

        $reloaded = $this->recipes->ofId($recipe->id());
        self::assertSame('Beet soup', $reloaded?->title()->value());
        self::assertSame([[self::BEET, '0.500'], [self::POTATO, '2.000']], self::flatten($reloaded->ingredients()));
        self::assertSame(2, $this->ingredientRows());
    }

    public function testUpdatingTwiceWithoutClearingKeepsWorking(): void
    {
        $recipe = $this->newRecipe('Borscht', [[self::POTATO, 1]]);

        $recipe->update(new RecipeTitle('Borscht'), new RecipeDescription('Simmer.'), self::ingredients([[self::POTATO, 2]]));
        $this->recipes->save($recipe);
        $recipe->update(new RecipeTitle('Borscht'), new RecipeDescription('Simmer.'), self::ingredients([[self::POTATO, 3], [self::BEET, 1]]));
        $this->recipes->save($recipe);
        $this->em->clear();

        self::assertSame(
            [[self::POTATO, '3.000'], [self::BEET, '1.000']],
            self::flatten($this->recipes->ofId($recipe->id())?->ingredients() ?? []),
        );
        self::assertSame(2, $this->ingredientRows());
    }

    public function testRemoveDeletesTheRecipeAndItsIngredients(): void
    {
        $recipe = $this->newRecipe('Borscht', [[self::POTATO, 1], [self::BEET, 0.5]]);
        $kept = $this->newRecipe('Deruny', [[self::POTATO, 1]]);

        $this->recipes->remove($recipe);
        $this->em->clear();

        self::assertNull($this->recipes->ofId($recipe->id()));
        self::assertNotNull($this->recipes->ofId($kept->id()));
        self::assertSame(1, $this->ingredientRows());
    }

    public function testPageIsOrderedByTitleThenId(): void
    {
        $this->newRecipe('Varenyky', [[self::POTATO, 1]]);
        $first = $this->newRecipe('Borscht', [[self::BEET, 1]]);
        $second = $this->newRecipe('Borscht', [[self::ONION, 1]]);
        $this->newRecipe('Holubtsi', [[self::ONION, 1]]);
        $this->em->clear();

        $page = $this->recipes->page(0, 10);

        self::assertSame(['Borscht', 'Borscht', 'Holubtsi', 'Varenyky'], self::titles($page));
        // UUID v7 ids grow over time, so the earlier recipe comes first among equal titles.
        self::assertTrue($page[0]->id()->equals($first->id()));
        self::assertTrue($page[1]->id()->equals($second->id()));
        self::assertSame([[self::BEET, '1.000']], self::flatten($page[0]->ingredients()));
    }

    public function testPageAppliesOffsetAndLimit(): void
    {
        foreach (['Varenyky', 'Borscht', 'Holubtsi', 'Deruny'] as $title) {
            $this->newRecipe($title, [[self::POTATO, 1], [self::ONION, 0.5]]);
        }
        $this->em->clear();

        self::assertSame(['Deruny', 'Holubtsi'], self::titles($this->recipes->page(1, 2)));
        self::assertSame([], $this->recipes->page(4, 2));
    }

    /**
     * @param list<Recipe> $recipes
     *
     * @return list<string>
     */
    private static function titles(array $recipes): array
    {
        return array_map(static fn (Recipe $r): string => $r->title()->value(), $recipes);
    }

    public function testOfIdReturnsNullForAnUnknownId(): void
    {
        self::assertNull($this->recipes->ofId($this->recipes->nextIdentity()));
    }

    private function ingredientRows(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM recipe_ingredients');
    }

    /**
     * @param list<array{string, int|float}> $ingredients
     */
    private function newRecipe(string $title, array $ingredients): Recipe
    {
        $recipe = new Recipe(
            $this->recipes->nextIdentity(),
            new RecipeTitle($title),
            new RecipeDescription('Simmer for an hour.'),
            self::ingredients($ingredients),
        );
        $this->recipes->save($recipe);

        return $recipe;
    }

    /**
     * @param list<array{string, int|float}> $ingredients
     *
     * @return list<Ingredient>
     */
    private static function ingredients(array $ingredients): array
    {
        return array_map(
            static fn (array $i): Ingredient => new Ingredient(ProductId::fromString($i[0]), Quantity::fromNumber($i[1])),
            $ingredients,
        );
    }

    /**
     * @param list<Ingredient> $ingredients
     *
     * @return list<array{string, string}>
     */
    private static function flatten(array $ingredients): array
    {
        return array_map(
            static fn (Ingredient $i): array => [$i->productId()->toString(), $i->quantity()->value()],
            $ingredients,
        );
    }
}
