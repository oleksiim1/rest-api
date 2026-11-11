<?php

declare(strict_types=1);

namespace App\Recipes\Presentation\Cli;

use App\Recipes\Application\AvailableProducts;
use App\Recipes\Application\CreateRecipe\CreateRecipe;
use App\Recipes\Application\CreateRecipe\CreateRecipeHandler;
use App\Recipes\Application\IngredientInput;
use App\Recipes\Domain\ProductId;
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
    name: 'app:recipes:seed-recipes',
    description: 'Adds fake recipes made from existing Catalog products. Every run adds new recipes.',
)]
final class SeedRecipesCommand extends Command
{
    private const MIN_INGREDIENTS = 2;
    private const MAX_INGREDIENTS = 6;

    public function __construct(
        private readonly CreateRecipeHandler $createRecipe,
        private readonly AvailableProducts $products,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'How many recipes to create', '10')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Faker seed, for repeatable output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = self::positiveInt($input->getOption('count'));
        if (null === $count) {
            $io->error('--count must be a positive integer.');

            return Command::INVALID;
        }

        $seed = $input->getOption('seed');
        if (null !== $seed && null === self::positiveInt($seed)) {
            $io->error('--seed must be a positive integer.');

            return Command::INVALID;
        }

        $productIds = array_map(static fn (ProductId $id): string => $id->toString(), $this->products->ids());
        if (\count($productIds) < self::MIN_INGREDIENTS) {
            $io->error(\sprintf(
                'A recipe needs at least %d products, but the catalog has %d. Run app:catalog:seed-products first.',
                self::MIN_INGREDIENTS,
                \count($productIds),
            ));

            return Command::FAILURE;
        }

        $faker = Factory::create();
        if (null !== $seed) {
            $faker->seed((int) $seed);
        }
        $maxIngredients = min(self::MAX_INGREDIENTS, \count($productIds));

        for ($i = 0; $i < $count; ++$i) {
            $ingredients = array_map(
                static fn (string $productId): IngredientInput => new IngredientInput($productId, $faker->randomFloat(1, 0.1, 5)),
                $faker->randomElements($productIds, $faker->numberBetween(self::MIN_INGREDIENTS, $maxIngredients)),
            );

            $recipe = ($this->createRecipe)(new CreateRecipe(
                ucfirst($faker->words($faker->numberBetween(2, 4), true)),
                $faker->paragraph(),
                $ingredients,
            ));
            $io->writeln(\sprintf('  <info>+</info> %s', $recipe->title), OutputInterface::VERBOSITY_VERBOSE);
        }

        $io->success(\sprintf('Created %d recipe(s).', $count));

        return Command::SUCCESS;
    }

    private static function positiveInt(mixed $value): ?int
    {
        if (!\is_string($value) || 1 !== preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }

        $int = filter_var($value, \FILTER_VALIDATE_INT);

        return false === $int ? null : $int;
    }
}
