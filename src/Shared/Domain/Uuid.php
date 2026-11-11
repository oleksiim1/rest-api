<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * UUID validation shared by the id value objects. Each context keeps its own id types and
 * exceptions; only the format check lives here.
 */
final class Uuid
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D';

    private function __construct()
    {
    }

    /**
     * Returns the lowercase form of a UUID, or null if the value is not one.
     */
    public static function normalise(string $value): ?string
    {
        $normalised = strtolower($value);

        return 1 === preg_match(self::PATTERN, $normalised) ? $normalised : null;
    }
}
