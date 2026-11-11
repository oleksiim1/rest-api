<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Cli;

use App\Shared\Presentation\Cli\PositiveIntOption;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PositiveIntOptionTest extends TestCase
{
    public function testItParsesAPositiveInteger(): void
    {
        self::assertSame(42, PositiveIntOption::parse('42'));
    }

    #[DataProvider('invalidValues')]
    public function testItRejectsAnythingElse(mixed $value): void
    {
        self::assertNull(PositiveIntOption::parse($value));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidValues(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'zero' => ['0'];
        yield 'negative' => ['-2'];
        yield 'leading zero' => ['007'];
        yield 'decimal' => ['1.5'];
        yield 'word' => ['three'];
        yield 'trailing newline' => ["3\n"];
        yield 'overflow' => ['99999999999999999999'];
        yield 'not a string' => [[3]];
    }
}
