<?php

namespace S2low\Command\Boot;

use S2lowLegacy\Boot\S2lowBootstrap;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'init:bootstrap',
    description: 'Bootstrap S2low',
)]
class S2lowBootstrapCommand extends Command
{
    public function __construct(
        private readonly S2lowBootstrap $bootstrap
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap->bootstrap();
        return Command::SUCCESS;
    }
}
