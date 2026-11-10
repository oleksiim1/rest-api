<?php

declare(strict_types=1);

namespace App\Recipes\Infrastructure\Persistence\Doctrine\Type;

use App\Recipes\Domain\Exception\InvalidRecipeId;
use App\Recipes\Domain\RecipeId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\GuidType;

/**
 * Stores RecipeId in a native uuid column (Postgres) and hydrates it back as a value object.
 */
final class RecipeIdType extends GuidType
{
    public const NAME = 'recipe_id';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof RecipeId) {
            return $value->toString();
        }

        throw InvalidType::new($value, self::NAME, ['null', RecipeId::class]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RecipeId
    {
        if (null === $value || $value instanceof RecipeId) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        try {
            return RecipeId::fromString($value);
        } catch (InvalidRecipeId $e) {
            throw new ConversionException($e->getMessage(), 0, $e);
        }
    }
}
