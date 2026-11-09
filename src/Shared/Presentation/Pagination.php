<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use App\Shared\Presentation\Exception\InvalidQueryParameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reads the "limit" and "offset" query parameters.
 */
final class Pagination
{
    private function __construct(
        public readonly int $limit,
        public readonly int $offset,
    ) {
    }

    /**
     * @throws InvalidQueryParameter when a parameter is not an integer in range
     */
    public static function fromRequest(Request $request, int $defaultLimit, int $maxLimit): self
    {
        return new self(
            self::int($request, 'limit', $defaultLimit, 1, $maxLimit),
            self::int($request, 'offset', 0, 0, \PHP_INT_MAX),
        );
    }

    private static function int(Request $request, string $name, int $default, int $min, int $max): int
    {
        $raw = $request->query->all()[$name] ?? null;
        if (null === $raw) {
            return $default;
        }

        $value = \is_string($raw) ? filter_var($raw, \FILTER_VALIDATE_INT) : false;
        if (false === $value || $value < $min || $value > $max) {
            throw new InvalidQueryParameter(\PHP_INT_MAX === $max
                ? \sprintf('"%s" must be an integer of at least %d.', $name, $min)
                : \sprintf('"%s" must be an integer between %d and %d.', $name, $min, $max));
        }

        return $value;
    }
}
