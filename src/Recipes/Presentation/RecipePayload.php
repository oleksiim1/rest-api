<?php

declare(strict_types=1);

namespace App\Recipes\Presentation;

use App\Recipes\Application\IngredientInput;
use App\Shared\Presentation\Exception\InvalidRequestBody;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks the shape and types of a recipe request body. Business rules (title length,
 * positive quantities, known products) are left to the Domain and Application.
 */
final class RecipePayload
{
    /**
     * @param list<IngredientInput> $ingredients
     */
    private function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly array $ingredients,
    ) {
    }

    /**
     * @throws \JsonException      when the body is not valid JSON
     * @throws InvalidRequestBody when the JSON does not match the expected shape
     */
    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        if (!self::isObject($data)) {
            throw new InvalidRequestBody('Request body must be a JSON object.');
        }

        $ingredients = $data['ingredients'] ?? null;
        if (!\is_array($ingredients) || !array_is_list($ingredients)) {
            throw new InvalidRequestBody('"ingredients" must be a list.');
        }

        return new self(
            self::string($data, 'title', 'title'),
            self::string($data, 'description', 'description'),
            array_map(self::ingredient(...), $ingredients, array_keys($ingredients)),
        );
    }

    private static function ingredient(mixed $ingredient, int $index): IngredientInput
    {
        $path = \sprintf('ingredients[%d]', $index);

        if (!self::isObject($ingredient)) {
            throw new InvalidRequestBody(\sprintf('"%s" must be an object with "productId" and "quantity".', $path));
        }

        $quantity = $ingredient['quantity'] ?? null;
        if (!\is_int($quantity) && !\is_float($quantity)) {
            throw new InvalidRequestBody(\sprintf('"%s.quantity" must be a number.', $path));
        }

        return new IngredientInput(self::string($ingredient, 'productId', $path.'.productId'), $quantity);
    }

    /**
     * @phpstan-assert-if-true array<mixed> $value
     */
    private static function isObject(mixed $value): bool
    {
        // json_decode() turns {} into [] too, so only a non-empty list is rejected.
        return \is_array($value) && ([] === $value || !array_is_list($value));
    }

    /** @param array<mixed> $data */
    private static function string(array $data, string $key, string $path): string
    {
        if (!\is_string($data[$key] ?? null)) {
            throw new InvalidRequestBody(\sprintf('"%s" must be a string.', $path));
        }

        return $data[$key];
    }
}
