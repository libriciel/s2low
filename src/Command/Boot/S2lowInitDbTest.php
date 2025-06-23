<?php

namespace S2low\Command\Boot;

use S2lowLegacy\Controller\PostgreSQLController;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'init:db-test',
    description: 'Bootstrap S2low',
)]
class S2lowInitDbTest extends Command
{
    public function __construct(
        private readonly PostgreSQLController $postgreSQLController,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->postgreSQLController->alterDatabase(function ($m) {
            echo "[Mise a  jour base de donnees de test]" . $m . "\n";
        });

        return Command::SUCCESS;
    }
}
