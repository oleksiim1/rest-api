<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Application;

use App\Recipes\Application\RecipeView;
use App\Recipes\Application\SearchRecipes\SearchRecipes;
use App\Recipes\Application\SearchRecipes\SearchRecipesHandler;
use App\Shared\Application\PageRequest;
use PHPUnit\Framework\TestCase;

final class SearchRecipesHandlerTest extends TestCase
{
    private InMemoryRecipeSearchIndex $index;
    private SearchRecipesHandler $handler;

    protected function setUp(): void
    {
        $this->index = new InMemoryRecipeSearchIndex();
        $this->handler = new SearchRecipesHandler($this->index);

        $this->index->index(self::view(1, 'Potato soup', 'Simmer the potatoes.'));
        $this->index->index(self::view(2, 'Borscht', 'Beets and a potato or two.'));
        $this->index->index(self::view(3, 'Varenyky', 'Dumplings with cherries.'));
        $this->index->index(self::view(4, 'Deruny', 'Grated potato pancakes.'));
    }

    public function testItReturnsTheMatchingRecipes(): void
    {
        $views = ($this->handler)(new SearchRecipes('potato'));

        self::assertSame(['Borscht', 'Deruny', 'Potato soup'], self::titles($views));
        self::assertEquals(self::view(2, 'Borscht', 'Beets and a potato or two.'), $views[0]);
    }

    public function testItReturnsNothingWhenNothingMatches(): void
    {
        self::assertSame([], ($this->handler)(new SearchRecipes('pierogi')));
    }

    public function testItAppliesLimitAndOffset(): void
    {
        $views = ($this->handler)(new SearchRecipes('potato', new PageRequest(limit: 1, offset: 1)));

        self::assertSame(['Deruny'], self::titles($views));
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
        return new RecipeView(\sprintf('00000000-0000-7000-9000-%012d', $n), $title, $description, [
            ['productId' => '00000000-0000-7000-8000-000000000001', 'quantity' => 1.0],
        ]);
    }
}
