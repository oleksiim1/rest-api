<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Cli;

/**
 * Parses a console option that must be a positive integer, e.g. --count=10.
 */
final class PositiveIntOption
{
    /**
     * @return int|null null when the value is not a positive integer written in plain digits
     */
    public static function parse(mixed $value): ?int
    {
        if (!\is_string($value) || 1 !== preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }

        $int = filter_var($value, \FILTER_VALIDATE_INT);

        return false === $int ? null : $int;
    }
}
