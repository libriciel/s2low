<?php

namespace S2low\Command;

use Psr\Log\LoggerInterface;
use S2low\Services\LogTimestampTokenGarbage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractAndDeleteTimestampTokenCommand extends Command
{
    private $logTimestampTokenGarbage;
    private $s2lowLogger;

    private const OLDER_THAN = "older-than";

    public function __construct(
        LogTimestampTokenGarbage $logTimestampTokenGarbage,
        LoggerInterface $s2lowLogger
    ) {
        $this->logTimestampTokenGarbage = $logTimestampTokenGarbage;
        $this->s2lowLogger = $s2lowLogger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $old_timestamp_token_directory = $this->logTimestampTokenGarbage->getOldTimestampTokenDirectory();
        $timestamp_token_retention_nb_days = $this->logTimestampTokenGarbage->getTimestampTokenRetentionNbDays();
        $this
            ->setName('log:timestamp-token-extract-and-delete')
            ->setDescription(
                "Extract the timestamp token oldest than $timestamp_token_retention_nb_days days (by default) from the database (table logs_historique), save it to $old_timestamp_token_directory (by default) and delete it from database"
            )
            ->addOption(
                "limit",
                "l",
                InputOption::VALUE_REQUIRED,
                "Limit the number of processed lines (0 for no limit)",
                0
            )
            ->addOption(
                "directory",
                "d",
                InputOption::VALUE_REQUIRED,
                "Override default repository",
                $old_timestamp_token_directory
            )
            ->addOption(
                self::OLDER_THAN,
                "o",
                InputOption::VALUE_REQUIRED,
                "Override default oldest date in days",
                $timestamp_token_retention_nb_days
            )
            ->addOption(
                'dry-run',
                '',
                InputOption::VALUE_NONE,
                "do not process"
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                "process without questions"
            )
        ;
    }

    private function askIfNeeded(InputInterface $input, SymfonyStyle $io): bool
    {
        if ($input->getOption('dry-run')) {
            $io->writeln("Dry run mode : halt");
            $io->success('Pass');
            return false;
        }
        if (! $input->getOption('force')) {
            $question = new ConfirmationQuestion("Are you sure ?", false);
            $response = $io->askQuestion($question);
            if (! $response) {
                $this->s2lowLogger->notice("Operation canceled");
                $io->success('Cancel');
                return false;
            }
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $limit = (int)$input->getOption('limit');

        if ($input->getOption(self::OLDER_THAN)) {
            $this->logTimestampTokenGarbage->setTimestampTokenRetentionNbDays(
                (int)$input->getOption(self::OLDER_THAN)
            );
        }

        if ($input->getOption('directory')) {
            $this->logTimestampTokenGarbage->setOldTimestampTokenDirectory(
                $input->getOption('directory')
            );
        }

        $info = $this->logTimestampTokenGarbage->getInfo($limit);

        $io->writeln("Found {$info['nb_result']} line(s)");
        if ($info['nb_result'] === 0) {
            $io->success('Pass');
            return 0;
        }

        $io->writeln("Line with min date : {$info['min_date']['date']} (id={$info['min_date']['id']})");
        $io->writeln("Line with max date : {$info['max_date']['date']} (id={$info['max_date']['id']})");

        if (! $this->askIfNeeded($input, $io)) {
            return 0;
        }

        $this->logTimestampTokenGarbage->extractAndDelete($limit);
        $io->success('Done');
        return 0;
    }
}
