<?php

declare(strict_types=1);

namespace App\Tests\Catalog\Presentation\Cli;

use App\Catalog\Domain\ProductRepository;
use App\Catalog\Presentation\Cli\ProductSeedData;
use App\Tests\Catalog\TruncatesProducts;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedProductsCommandTest extends KernelTestCase
{
    use TruncatesProducts;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        self::truncateProducts(self::getContainer());

        $this->tester = new CommandTester((new Application($kernel))->find('app:catalog:seed-products'));
    }

    public function testItCreatesEveryProduct(): void
    {
        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        $count = \count(ProductSeedData::PRODUCTS);
        self::assertCount($count, self::getContainer()->get(ProductRepository::class)->page(0, 1000));
        self::assertStringContainsString(\sprintf('Created %d, skipped 0', $count), $this->tester->getDisplay());
    }

    public function testRunningItAgainSkipsExistingProducts(): void
    {
        $this->tester->execute([]);
        $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $this->tester->getStatusCode());
        self::assertCount(\count(ProductSeedData::PRODUCTS), self::getContainer()->get(ProductRepository::class)->page(0, 1000));
        self::assertStringContainsString(
            \sprintf('Created 0, skipped %d', \count(ProductSeedData::PRODUCTS)),
            $this->tester->getDisplay(),
        );
    }
}
