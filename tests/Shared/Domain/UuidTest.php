<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\Uuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    private const ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    public function testItReturnsAValidUuidUnchanged(): void
    {
        self::assertSame(self::ID, Uuid::normalise(self::ID));
    }

    public function testItLowercasesAValidUuid(): void
    {
        self::assertSame(self::ID, Uuid::normalise(strtoupper(self::ID)));
    }

    #[DataProvider('malformedValues')]
    public function testItReturnsNullForAMalformedValue(string $value): void
    {
        self::assertNull(Uuid::normalise($value));
    }

    /** @return iterable<string, array{string}> */
    public static function malformedValues(): iterable
    {
        yield 'empty' => [''];
        yield 'not a uuid' => ['pancakes'];
        yield 'missing a group' => ['0193a2b4-5c6d-7e8f-1c2d3e4f5a6b'];
        yield 'surrounding whitespace' => [' '.self::ID];
        yield 'trailing newline' => [self::ID."\n"];
        yield 'non-hex digit' => ['0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6g'];
    }
}
