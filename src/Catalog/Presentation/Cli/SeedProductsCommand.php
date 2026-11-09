<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Cli;

use App\Catalog\Application\CreateProduct\CreateProduct;
use App\Catalog\Application\CreateProduct\CreateProductHandler;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:catalog:seed-products',
    description: 'Adds sample market products (potato, rice, eggs...). Safe to re-run: existing names are skipped.',
)]
final class SeedProductsCommand extends Command
{
    public function __construct(private readonly CreateProductHandler $createProduct)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;
        $skipped = 0;

        foreach (ProductSeedData::PRODUCTS as $product) {
            try {
                ($this->createProduct)(new CreateProduct(
                    $product['name'],
                    $product['amount'],
                    $product['currency'],
                    $product['unit'],
                ));
                ++$created;
                $io->writeln(\sprintf('  <info>+</info> %s', $product['name']), OutputInterface::VERBOSITY_VERBOSE);
            } catch (ProductAlreadyExists) {
                ++$skipped;
                $io->writeln(\sprintf('  <comment>=</comment> %s (exists)', $product['name']), OutputInterface::VERBOSITY_VERBOSE);
            }
        }

        $io->success(\sprintf('Created %d, skipped %d existing product(s).', $created, $skipped));

        return Command::SUCCESS;
    }
}
