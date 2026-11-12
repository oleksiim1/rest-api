<?php

declare(strict_types=1);

namespace App\Recipes\Presentation;

use App\Shared\Presentation\Exception\InvalidQueryParameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reads the "q" search parameter.
 */
final class SearchQuery
{
    public const MAX_LENGTH = 200;

    /**
     * @return string|null the trimmed query, or null when the request has no "q"
     *
     * @throws InvalidQueryParameter when "q" is not a string, is blank or is too long
     */
    public static function fromRequest(Request $request): ?string
    {
        $raw = $request->query->all()['q'] ?? null;
        if (null === $raw) {
            return null;
        }

        if (!\is_string($raw)) {
            throw new InvalidQueryParameter('"q" must be a string.');
        }

        $query = trim($raw);
        if ('' === $query) {
            throw new InvalidQueryParameter('"q" must not be empty.');
        }

        if (mb_strlen($query) > self::MAX_LENGTH) {
            throw new InvalidQueryParameter(\sprintf('"q" must be at most %d characters.', self::MAX_LENGTH));
        }

        return $query;
    }
}
