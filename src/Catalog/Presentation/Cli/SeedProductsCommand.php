<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Cli;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Unit;
use App\Shared\Presentation\Cli\PositiveIntOption;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dev/test only (see config/services.yaml): Faker is a dev dependency.
 */
#[AsCommand(
    name: 'app:catalog:seed-products',
    description: 'Adds real market products (potato, rice, eggs...) with fake prices and units. Existing names are skipped.',
)]
final class SeedProductsCommand extends Command
{
    private const CURRENCY = 'UAH';

    public function __construct(private readonly CreateProductHandler $createProduct)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, \sprintf('How many products to add (at most %d)', \count(ProductSeedData::NAMES)), '10')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Faker seed, for repeatable output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = PositiveIntOption::parse($input->getOption('count'));
        if (null === $count || $count > \count(ProductSeedData::NAMES)) {
            $io->error(\sprintf('--count must be an integer between 1 and %d.', \count(ProductSeedData::NAMES)));

            return Command::INVALID;
        }

        $seed = $input->getOption('seed');
        if (null !== $seed && null === PositiveIntOption::parse($seed)) {
            $io->error('--seed must be a positive integer.');

            return Command::INVALID;
        }

        $faker = Factory::create();
        if (null !== $seed) {
            $faker->seed((int) $seed);
        }

        $created = 0;
        $skipped = 0;

        foreach ($faker->randomElements(ProductSeedData::NAMES, $count) as $name) {
            try {
                ($this->createProduct)(new CreateProduct(
                    $name,
                    // 10.00 to 500.00 UAH in steps of 0.50, stored in kopiyky.
                    $faker->numberBetween(20, 1000) * 50,
                    self::CURRENCY,
                    $faker->randomElement(Unit::cases())->value,
                ));
                ++$created;
                $io->writeln(\sprintf('  <info>+</info> %s', $name), OutputInterface::VERBOSITY_VERBOSE);
            } catch (ProductAlreadyExists) {
                ++$skipped;
                $io->writeln(\sprintf('  <comment>=</comment> %s (exists)', $name), OutputInterface::VERBOSITY_VERBOSE);
            }
        }

        $io->success(\sprintf('Created %d, skipped %d existing product(s).', $created, $skipped));

        return Command::SUCCESS;
    }
}
