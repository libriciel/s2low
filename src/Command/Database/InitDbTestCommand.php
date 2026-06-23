<?php

namespace S2low\Command\Database;

use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'db:test-init',
    description: 'Initialise la base de données de test (reconfiguration schéma public, import s2low.sql, migrations, fixtures et sequences).',
)]
class InitDbTestCommand extends Command
{
    public function __construct(
        private readonly SQLQuery $sqlQuery,
        private readonly string $projectDirectory,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->kernel->getEnvironment() !== 'test') {
            $output->writeln("<error>Cette commande ne peut être exécutée qu'en environnement 'test' !</error>");
            return Command::FAILURE;
        }

        $output->writeln("=== Database Initialization BEGIN ===");

        $output->writeln("Dropping/recreating public schema...");
        $this->sqlQuery->exec("DROP SCHEMA IF EXISTS public CASCADE");
        $this->sqlQuery->exec("CREATE SCHEMA public");
        $this->sqlQuery->exec("GRANT ALL ON SCHEMA public TO public");

        $sqlPath = $this->projectDirectory . '/db/s2low.sql';
        $output->writeln("Loading db/s2low.sql from $sqlPath...");
        $this->sqlQuery->exec(file_get_contents($sqlPath));

        $output->writeln("Running doctrine migrations...");
        $application = $this->getApplication();
        if ($application === null) {
            $application = new Application($this->kernel);
        }
        $migrationCommand = $application->find('doctrine:migrations:migrate');
        $migrationInput = new ArrayInput(['--no-interaction' => true]);
        $migrationCommand->run($migrationInput, $output);

        $fixturesPath = $this->projectDirectory . '/test/PHPUnit/s2low-test.sql';
        $output->writeln("Loading fixtures s2low-test.sql from $fixturesPath...");
        $this->sqlQuery->exec(file_get_contents($fixturesPath));

        $output->writeln("=== Database Initialization END ===");
        return Command::SUCCESS;
    }
}
