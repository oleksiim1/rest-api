<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Infrastructure\Persistence\Doctrine\Type;

use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\ProductId;
use App\Catalog\Infrastructure\Persistence\Doctrine\Type\ProductIdType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductIdTypeTest extends KernelTestCase
{
    private const ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    private Type $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        $this->type = Type::getType(ProductIdType::NAME);
        $this->platform = $em->getConnection()->getDatabasePlatform();
    }

    public function testItIsRegisteredWithDoctrine(): void
    {
        self::assertInstanceOf(ProductIdType::class, $this->type);
    }

    public function testItStoresAProductIdAsItsString(): void
    {
        self::assertSame(self::ID, $this->type->convertToDatabaseValue(ProductId::fromString(self::ID), $this->platform));
        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    public function testItRefusesToStoreAnythingButAProductId(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(self::ID, $this->platform);
    }

    public function testItHydratesAProductId(): void
    {
        $id = $this->type->convertToPHPValue(self::ID, $this->platform);

        self::assertInstanceOf(ProductId::class, $id);
        self::assertSame(self::ID, $id->toString());
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testAProductIdPassesThrough(): void
    {
        $id = ProductId::fromString(self::ID);

        self::assertSame($id, $this->type->convertToPHPValue($id, $this->platform));
    }

    public function testAnInvalidStringIsAConversionError(): void
    {
        try {
            $this->type->convertToPHPValue('not-a-uuid', $this->platform);
            self::fail('Expected ConversionException.');
        } catch (ConversionException $e) {
            self::assertInstanceOf(InvalidProductId::class, $e->getPrevious());
        }
    }

    #[DataProvider('nonStrings')]
    public function testANonStringIsAConversionError(mixed $value): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue($value, $this->platform);
    }

    /** @return iterable<string, array{mixed}> */
    public static function nonStrings(): iterable
    {
        yield 'int' => [42];
        yield 'array' => [[self::ID]];
        yield 'object' => [new \stdClass()];
    }
}
