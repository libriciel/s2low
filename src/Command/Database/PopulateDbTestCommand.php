<?php

namespace S2low\Command\Database;

use S2lowLegacy\Boot\S2lowBootstrap;
use S2lowLegacy\Controller\PostgreSQLController;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:test-populate',
    description: 'Ajoute des donnes de test à la base de donnees de test.',
)]
class PopulateDbTestCommand extends Command
{
    public function __construct(
        private readonly PostgreSQLController $postgreSQLController
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->postgreSQLController->populateDbTest();
        return Command::SUCCESS;
    }
}
