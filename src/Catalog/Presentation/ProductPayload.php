<?php

declare(strict_types=1);

namespace App\Catalog\Presentation;

use App\Shared\Presentation\Exception\InvalidRequestBody;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks the shape and types of a product request body. Business rules
 * (name length, currency format, known units) are left to the Domain.
 */
final class ProductPayload
{
    private function __construct(
        public readonly string $name,
        public readonly int $priceAmount,
        public readonly string $priceCurrency,
        public readonly string $unit,
    ) {
    }

    /**
     * @throws \JsonException      when the body is not valid JSON
     * @throws InvalidRequestBody when the JSON does not match the expected shape
     */
    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($data) || ([] !== $data && array_is_list($data))) {
            throw new InvalidRequestBody('Request body must be a JSON object.');
        }

        $price = $data['price'] ?? null;
        if (!\is_array($price)) {
            throw new InvalidRequestBody('"price" must be an object with "amount" and "currency".');
        }

        return new self(
            self::string($data, 'name'),
            self::int($price, 'amount', 'price.amount'),
            self::string($price, 'currency', 'price.currency'),
            self::string($data, 'unit'),
        );
    }

    /** @param array<mixed> $data */
    private static function string(array $data, string $key, ?string $path = null): string
    {
        if (!\is_string($data[$key] ?? null)) {
            throw new InvalidRequestBody(\sprintf('"%s" must be a string.', $path ?? $key));
        }

        return $data[$key];
    }

    /** @param array<mixed> $data */
    private static function int(array $data, string $key, string $path): int
    {
        if (!\is_int($data[$key] ?? null)) {
            throw new InvalidRequestBody(\sprintf('"%s" must be an integer (minor units, e.g. 2500 for 25.00).', $path));
        }

        return $data[$key];
    }
}
