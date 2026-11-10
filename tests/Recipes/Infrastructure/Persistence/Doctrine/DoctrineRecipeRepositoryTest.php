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
use App\Tests\Recipes\TruncatesRecipes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineRecipeRepositoryTest extends KernelTestCase
{
    use TruncatesRecipes;

    private const POTATO = '00000000-0000-7000-8000-000000000001';
    private const ONION = '00000000-0000-7000-8000-000000000002';
    private const BEET = '00000000-0000-7000-8000-000000000003';

    private RecipeRepository $recipes;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::truncateRecipes($container);

        // Built directly: nothing injects the repository until the Presentation layer, so the
        // container removes the private alias. The controller tests cover the binding.
        $this->recipes = new DoctrineRecipeRepository($container->get('doctrine'));
        $this->em = $container->get(EntityManagerInterface::class);
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

    public function testOfIdReturnsNullForAnUnknownId(): void
    {
        self::assertNull($this->recipes->ofId($this->recipes->nextIdentity()));
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
