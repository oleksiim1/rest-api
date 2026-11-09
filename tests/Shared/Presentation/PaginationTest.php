<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation;

use App\Shared\Presentation\Exception\InvalidQueryParameter;
use App\Shared\Presentation\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class PaginationTest extends TestCase
{
    public function testItUsesDefaultsWhenNothingIsGiven(): void
    {
        $page = Pagination::fromRequest(new Request(), 50, 100);

        self::assertSame(50, $page->limit);
        self::assertSame(0, $page->offset);
    }

    public function testItReadsLimitAndOffset(): void
    {
        $page = Pagination::fromRequest(new Request(['limit' => '100', 'offset' => '20']), 50, 100);

        self::assertSame(100, $page->limit);
        self::assertSame(20, $page->offset);
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('invalidQueries')]
    public function testItRejectsInvalidValues(array $query): void
    {
        $this->expectException(InvalidQueryParameter::class);

        Pagination::fromRequest(new Request($query), 50, 100);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidQueries(): iterable
    {
        yield 'limit zero' => [['limit' => '0']];
        yield 'limit above max' => [['limit' => '101']];
        yield 'limit not a number' => [['limit' => 'ten']];
        yield 'limit a decimal' => [['limit' => '1.5']];
        yield 'limit empty' => [['limit' => '']];
        yield 'limit an array' => [['limit' => ['1']]];
        yield 'negative offset' => [['offset' => '-1']];
    }
}
