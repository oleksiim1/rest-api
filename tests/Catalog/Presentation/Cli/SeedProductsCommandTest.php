<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Cli;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductRepository;
use App\Catalog\Presentation\Cli\ProductSeedData;
use App\Tests\TruncatesTables;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedProductsCommandTest extends KernelTestCase
{
    use TruncatesTables;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        self::truncateTables(self::getContainer(), 'products');

        $this->tester = new CommandTester((new Application($kernel))->find('app:catalog:seed-products'));
    }

    public function testItCreatesTheRequestedNumberOfRealProductsWithFakePrices(): void
    {
        $this->tester->execute(['--count' => '5']);

        $this->tester->assertCommandIsSuccessful();
        $products = $this->products();
        self::assertCount(5, $products);
        self::assertStringContainsString('Created 5, skipped 0', $this->tester->getDisplay());

        foreach ($products as $product) {
            self::assertContains($product->name()->value(), ProductSeedData::NAMES);
            self::assertGreaterThan(0, $product->price()->amount());
            self::assertSame('UAH', $product->price()->currency());
        }
    }

    public function testItCreatesTenProductsByDefault(): void
    {
        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertCount(10, $this->products());
    }

    #[DataProvider('invalidCounts')]
    public function testItRejectsACountOutsideTheAvailableNames(string $count): void
    {
        $status = $this->tester->execute(['--count' => $count]);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString(
            \sprintf('--count must be an integer between 1 and %d', \count(ProductSeedData::NAMES)),
            $this->tester->getDisplay(),
        );
        self::assertCount(0, $this->products());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCounts(): iterable
    {
        yield 'zero' => ['0'];
        yield 'word' => ['three'];
        yield 'more than there are names' => [(string) (\count(ProductSeedData::NAMES) + 1)];
    }

    public function testRunningItAgainSkipsExistingProducts(): void
    {
        $all = (string) \count(ProductSeedData::NAMES);

        $this->tester->execute(['--count' => $all]);
        $this->tester->execute(['--count' => $all]);

        $this->tester->assertCommandIsSuccessful();
        self::assertCount(\count(ProductSeedData::NAMES), $this->products());
        self::assertStringContainsString(\sprintf('Created 0, skipped %s', $all), $this->tester->getDisplay());
    }

    public function testTheSameSeedProducesTheSameProducts(): void
    {
        $this->tester->execute(['--count' => '5', '--seed' => '42']);
        $first = $this->snapshot();
        self::truncateTables(self::getContainer(), 'products');
        $this->tester->execute(['--count' => '5', '--seed' => '42']);

        self::assertCount(5, $first);
        self::assertSame($first, $this->snapshot());
    }

    public function testItRejectsAnInvalidSeed(): void
    {
        $status = $this->tester->execute(['--seed' => 'abc']);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString('--seed must be a positive integer', $this->tester->getDisplay());
    }

    /**
     * @return list<string> name, price and unit of every product, ordered by name
     */
    private function snapshot(): array
    {
        return array_map(
            static fn (Product $p): string => \sprintf('%s %d %s', $p->name()->value(), $p->price()->amount(), $p->unit()->value),
            $this->products(),
        );
    }

    /**
     * @return list<Product>
     */
    private function products(): array
    {
        return self::getContainer()->get(ProductRepository::class)->page(0, 1000);
    }
}
