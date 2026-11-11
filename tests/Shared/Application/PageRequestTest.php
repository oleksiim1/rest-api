<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application;

use App\Shared\Application\PageRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PageRequestTest extends TestCase
{
    public function testItDefaultsToTheFirstPageOfDefaultSize(): void
    {
        $page = new PageRequest();

        self::assertSame(PageRequest::DEFAULT_LIMIT, $page->limit);
        self::assertSame(0, $page->offset);
    }

    public function testItKeepsAValidLimitAndOffset(): void
    {
        $page = new PageRequest(limit: PageRequest::MAX_LIMIT, offset: 30);

        self::assertSame(PageRequest::MAX_LIMIT, $page->limit);
        self::assertSame(30, $page->offset);
    }

    #[DataProvider('invalidPages')]
    public function testItRejectsAnOutOfRangePage(int $limit, int $offset, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new PageRequest($limit, $offset);
    }

    /** @return iterable<string, array{int, int, string}> */
    public static function invalidPages(): iterable
    {
        $limitMessage = \sprintf('Limit must be between 1 and %d.', PageRequest::MAX_LIMIT);

        yield 'zero limit' => [0, 0, $limitMessage];
        yield 'limit above the maximum' => [PageRequest::MAX_LIMIT + 1, 0, $limitMessage];
        yield 'negative offset' => [10, -1, 'Offset must not be negative.'];
    }
}
