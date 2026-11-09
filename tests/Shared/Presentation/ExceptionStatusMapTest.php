<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation;

use App\Shared\Presentation\ExceptionStatusMap;
use App\Shared\Presentation\ExceptionStatusMapping;
use PHPUnit\Framework\TestCase;

final class ExceptionStatusMapTest extends TestCase
{
    public function testItCombinesTheMappingsOfEveryContext(): void
    {
        $map = new ExceptionStatusMap([
            self::mapping([\JsonException::class => 400]),
            self::mapping([\DomainException::class => 422]),
        ]);

        self::assertSame(400, $map->statusFor(new \JsonException()));
        self::assertSame(422, $map->statusFor(new \DomainException()));
    }

    public function testASubclassUsesItsParentsStatus(): void
    {
        $map = new ExceptionStatusMap([self::mapping([\LogicException::class => 422])]);

        self::assertSame(422, $map->statusFor(new \DomainException()));
    }

    public function testTheMostSpecificClassWinsRegardlessOfOrder(): void
    {
        $map = new ExceptionStatusMap([
            self::mapping([\LogicException::class => 422]),
            self::mapping([\DomainException::class => 404]),
        ]);

        self::assertSame(404, $map->statusFor(new \DomainException()));
        self::assertSame(422, $map->statusFor(new \InvalidArgumentException()));
    }

    public function testAnUnmappedExceptionHasNoStatus(): void
    {
        $map = new ExceptionStatusMap([self::mapping([\DomainException::class => 422])]);

        self::assertNull($map->statusFor(new \RuntimeException()));
    }

    public function testMappingTheSameClassTwiceIsRejected(): void
    {
        $this->expectException(\LogicException::class);

        new ExceptionStatusMap([
            self::mapping([\DomainException::class => 422]),
            self::mapping([\DomainException::class => 404]),
        ]);
    }

    /**
     * @param array<class-string<\Throwable>, int> $statuses
     */
    private static function mapping(array $statuses): ExceptionStatusMapping
    {
        return new class($statuses) implements ExceptionStatusMapping {
            /** @param array<class-string<\Throwable>, int> $statuses */
            public function __construct(private readonly array $statuses)
            {
            }

            public function statuses(): array
            {
                return $this->statuses;
            }
        };
    }
}
