<?php

declare(strict_types=1);

namespace App\Tests\Recipes\Infrastructure\Persistence\Doctrine\Type;

use App\Recipes\Domain\Exception\InvalidRecipeId;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Infrastructure\Persistence\Doctrine\Type\RecipeIdType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RecipeIdTypeTest extends KernelTestCase
{
    private const ID = '0193a2b4-5c6d-7e8f-9a0b-1c2d3e4f5a6b';

    private Type $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        // Custom types are registered when the connection is created, so reach it first;
        // otherwise this test only passes after another test has opened the connection.
        $this->platform = $em->getConnection()->getDatabasePlatform();
        $this->type = Type::getType(RecipeIdType::NAME);
    }

    public function testItIsRegisteredWithDoctrine(): void
    {
        self::assertInstanceOf(RecipeIdType::class, $this->type);
    }

    public function testItStoresARecipeIdAsItsString(): void
    {
        self::assertSame(self::ID, $this->type->convertToDatabaseValue(RecipeId::fromString(self::ID), $this->platform));
        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    public function testItRefusesToStoreAnythingButARecipeId(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(self::ID, $this->platform);
    }

    public function testItHydratesARecipeId(): void
    {
        $id = $this->type->convertToPHPValue(self::ID, $this->platform);

        self::assertInstanceOf(RecipeId::class, $id);
        self::assertSame(self::ID, $id->toString());
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testARecipeIdPassesThrough(): void
    {
        $id = RecipeId::fromString(self::ID);

        self::assertSame($id, $this->type->convertToPHPValue($id, $this->platform));
    }

    public function testAnInvalidStringIsAConversionError(): void
    {
        try {
            $this->type->convertToPHPValue('not-a-uuid', $this->platform);
            self::fail('Expected ConversionException.');
        } catch (ConversionException $e) {
            self::assertInstanceOf(InvalidRecipeId::class, $e->getPrevious());
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
