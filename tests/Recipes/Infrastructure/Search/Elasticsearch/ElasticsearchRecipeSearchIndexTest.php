<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Search\Elasticsearch;

use App\Recipes\Application\RecipeSearchIndex;
use App\Recipes\Application\RecipeView;
use App\Recipes\Infrastructure\Search\Elasticsearch\ElasticsearchRecipeSearchIndex;
use App\Shared\Application\PageRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ElasticsearchRecipeSearchIndexTest extends KernelTestCase
{
    private RecipeSearchIndex $index;

    protected function setUp(): void
    {
        self::bootKernel();
        $index = self::getContainer()->get(RecipeSearchIndex::class);
        \assert($index instanceof RecipeSearchIndex);
        $this->index = $index;
        $this->index->rebuild([]);
    }

    public function testTheInterfaceIsBoundToTheElasticsearchImplementation(): void
    {
        self::assertInstanceOf(ElasticsearchRecipeSearchIndex::class, $this->index);
    }

    public function testAWordInTheTitleFindsTheRecipe(): void
    {
        $borscht = self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.');
        $this->index->index($borscht);
        $this->index->index(self::view(2, 'Varenyky', 'Dumplings with cherries.'));

        self::assertEquals([$borscht], $this->search('borscht'));
    }

    public function testAWordInTheDescriptionFindsTheRecipe(): void
    {
        $varenyky = self::view(2, 'Varenyky', 'Dumplings with cherries.');
        $this->index->index(self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.'));
        $this->index->index($varenyky);

        self::assertEquals([$varenyky], $this->search('cherries'));
    }

    public function testAMisspelledWordStillFindsTheRecipe(): void
    {
        $this->index->index(self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.'));

        self::assertSame(['Ukrainian borscht'], self::titles($this->search('borsht')));
        self::assertSame(['Ukrainian borscht'], self::titles($this->search('cabage')));
    }

    public function testATitleMatchRanksAboveADescriptionOnlyMatch(): void
    {
        $this->index->index(self::view(1, 'Apple pie', 'Bake it with a potato on the side.'));
        $this->index->index(self::view(2, 'Potato salad', 'Boil, cool and dress.'));

        self::assertSame(['Potato salad', 'Apple pie'], self::titles($this->search('potato')));
    }

    public function testLimitAndOffsetPageTheResults(): void
    {
        foreach (['Deruny', 'Borscht', 'Varenyky', 'Holubtsi'] as $n => $title) {
            $this->index->index(self::view($n + 1, $title, 'Uses a potato.'));
        }

        // Equal scores fall back to title order.
        self::assertSame(['Borscht', 'Deruny', 'Holubtsi', 'Varenyky'], self::titles($this->search('potato')));
        self::assertSame(['Deruny', 'Holubtsi'], self::titles($this->search('potato', new PageRequest(limit: 2, offset: 1))));
        self::assertSame([], $this->search('potato', new PageRequest(limit: 2, offset: 4)));
    }

    public function testAnOffsetPastTheResultWindowFindsNothingInsteadOfFailing(): void
    {
        $this->index->index(self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.'));

        self::assertSame([], $this->search('borscht', new PageRequest(limit: 100, offset: 9950)));
        self::assertSame([], $this->search('borscht', new PageRequest(limit: 1, offset: \PHP_INT_MAX)));
    }

    public function testIndexingAnExistingIdReplacesTheDocument(): void
    {
        $this->index->index(self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.'));
        $updated = self::view(1, 'Green borscht', 'Sorrel and eggs.');
        $this->index->index($updated);

        self::assertEquals([$updated], $this->search('borscht'));
        self::assertSame([], $this->search('cabbage'));
    }

    public function testRemoveMakesTheRecipeUnfindable(): void
    {
        $borscht = self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.');
        $this->index->index($borscht);

        $this->index->remove($borscht->id);

        self::assertSame([], $this->search('borscht'));
    }

    public function testRemovingAnUnknownIdDoesNothing(): void
    {
        $this->index->remove(self::id(99));

        self::assertSame([], $this->search('borscht'));
    }

    public function testRebuildDropsDocumentsThatAreNotInTheNewSet(): void
    {
        $this->index->index(self::view(1, 'Ukrainian borscht', 'Simmer beets with cabbage.'));
        $varenyky = self::view(2, 'Varenyky', 'Dumplings with cherries and a little borscht.');

        $this->index->rebuild([$varenyky]);

        self::assertEquals([$varenyky], $this->search('borscht'));
    }

    /**
     * @return list<RecipeView>
     */
    private function search(string $query, PageRequest $page = new PageRequest()): array
    {
        return $this->index->search($query, $page);
    }

    /**
     * @param list<RecipeView> $views
     *
     * @return list<string>
     */
    private static function titles(array $views): array
    {
        return array_map(static fn (RecipeView $view): string => $view->title, $views);
    }

    private static function view(int $n, string $title, string $description): RecipeView
    {
        return new RecipeView(self::id($n), $title, $description, [
            ['productId' => '00000000-0000-7000-8000-000000000001', 'quantity' => 1.5],
            ['productId' => '00000000-0000-7000-8000-000000000002', 'quantity' => 2.0],
        ]);
    }

    private static function id(int $n): string
    {
        return \sprintf('00000000-0000-7000-9000-%012d', $n);
    }
}
