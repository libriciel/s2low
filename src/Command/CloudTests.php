<?php

namespace S2low\Command;

use OpenStack\ObjectStore\v1\Models\Account;
use S2lowLegacy\Class\actes\ActesCloudStorage;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CloudTests extends Command
{
    public function __construct(private ActesCloudStorage $cloudStorage, private OpenStackSwiftWrapper $openStackSwiftWrapper)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('test:cloud')
            ->setDescription(
                "test cloud functions"
            )
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                "pathInCloud"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->openStackSwiftWrapper->fileExistsOnCloud(
            $this->cloudStorage->getContainerName(),
            $input->getArgument('path')
        );

        $output->writeln(var_export($result, true));
        return 0;
    }
}
