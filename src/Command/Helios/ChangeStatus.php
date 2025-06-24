<?php

declare(strict_types=1);

namespace S2low\Command\Helios;

use Exception;
use RuntimeException;
use S2low\Services\Helios\Statuses;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ChangeStatus extends Command
{
    private Statuses $statuses;

    /**
     * @throws Exception
     */
    public function __construct(
        private HeliosStatusSQL $statusSQL,
        private HeliosTransactionsSQL $transactionsSQL,
        $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName('helios:change-status')
            ->setDescription(
                "Permet de changer le statut d'une transaction helios"
            )
            ->addArgument(
                'transaction-id',
                InputArgument::REQUIRED,
                "l'id de la transaction modifiée"
            )
            ->addArgument(
                'status-id',
                InputArgument::REQUIRED,
                'id du statut désiré'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'force execution'
            );
        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->statuses = new Statuses($this->statusSQL->getAllStatus());

        $io = new SymfonyStyle($input, $output);

        try {
            $transactionId = (int) $input->getArgument('transaction-id');
            $statusId = (int) $input->getArgument('status-id');
            $force = $input->getOption('force');

            if (!$this->transactionsSQL->getInfo($transactionId)) {
                throw new RuntimeException('transaction_id incorrect : aucune transaction trouvée');
            }
            $nouveauStatut = $this->statuses->getNameById($statusId);
            $ancienStatut = $this->statuses->getNameById(
                (int)$this->transactionsSQL->getLatestStatusId($transactionId)
            );
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $io->table(['id','statut'], $this->statusSQL->getAllStatus());
            return Command::FAILURE;
        }

        if (
            $force || $io->confirm(
                "La transaction $transactionId passera de \"$ancienStatut\" à \"$nouveauStatut\"\n" .
                'Etes-vous sûr de vouloir continuer ?',
                false
            )
        ) {
            $this->transactionsSQL->updateStatus(
                $transactionId,
                $statusId,
                'Modification du statut en ligne de commande'
            );
            $io->info("Modification de la transaction $transactionId : status $nouveauStatut [$statusId]");
            return Command::SUCCESS;
        }
        $io->info('Changement de statut annulé');
        return Command::SUCCESS;
    }
}
