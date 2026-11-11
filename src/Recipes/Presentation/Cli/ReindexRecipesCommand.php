<?php

declare(strict_types=1);

namespace App\Recipes\Presentation\Cli;

use App\Recipes\Application\ReindexRecipes\ReindexRecipes;
use App\Recipes\Application\ReindexRecipes\ReindexRecipesHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recipes:reindex',
    description: 'Rebuilds the recipe search index from the database.',
)]
final class ReindexRecipesCommand extends Command
{
    public function __construct(private readonly ReindexRecipesHandler $reindex)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = ($this->reindex)(new ReindexRecipes());

        (new SymfonyStyle($input, $output))->success(\sprintf('Indexed %d recipe(s).', $count));

        return Command::SUCCESS;
    }
}
