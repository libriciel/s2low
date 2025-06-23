<?php

namespace S2low\Command\Database;

use S2lowLegacy\Boot\S2lowBootstrap;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'db:populate',
    description: 'Ajoute les : 
        helios-status, 
        authority-district, 
        authority_departments, 
        actes_status, 
        actes_natures, 
        modules,
        authority_types 
        pour que s2low puisse fonctionner.',
)]
class DbPopulateCommand extends Command
{
    public function __construct(
        private readonly S2lowBootstrap $bootstrap
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap->populateDatabase();
        return Command::SUCCESS;
    }
}
