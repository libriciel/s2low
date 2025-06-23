<?php

namespace S2low\Command\Database;

use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:create-first-authority',
    description: 'Cree la premiere collectivite (Authority).',
)]
class CreateFirstEntity extends Command
{
    public function __construct(
        private readonly SQLQuery $sqlQuery
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authority_id = $this->sqlQuery->queryOne(
            "INSERT INTO authorities (id, status, name) VALUES(nextval('authorities_id_seq'), 1, 'Administrateurs') RETURNING id"
        );

        $output->writeln("Création de l'entité $authority_id");

        return Command::SUCCESS;
    }
}
