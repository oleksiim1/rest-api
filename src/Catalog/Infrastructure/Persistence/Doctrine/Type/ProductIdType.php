<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine\Type;

use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\ProductId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\GuidType;

/**
 * Stores ProductId in a native uuid column (Postgres) and hydrates it back as a value object.
 */
final class ProductIdType extends GuidType
{
    public const NAME = 'product_id';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof ProductId) {
            return $value->toString();
        }

        throw InvalidType::new($value, self::NAME, ['null', ProductId::class]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ProductId
    {
        if (null === $value || $value instanceof ProductId) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        try {
            return ProductId::fromString($value);
        } catch (InvalidProductId $e) {
            throw new ConversionException($e->getMessage(), 0, $e);
        }
    }
}
