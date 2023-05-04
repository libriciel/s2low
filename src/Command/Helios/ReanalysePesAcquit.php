<?php

namespace S2low\Command\Helios;

use Exception;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReanalysePesAcquit extends Command
{
    /**
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function __construct(WorkerScript $workerScript)
    {
        $cloudStorageFactory = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(CloudStorageFactory::class);
        $this-> pesAcquitCloudStorage = $cloudStorageFactory->getInstanceByClassName(PESAcquitCloudStorage::class);
        $this->workerScript = $workerScript;
        $this->helios_ftp_response_tmp_local_path = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get("helios_ftp_response_tmp_local_path");
            //$helios_ftp_response_tmp_local_path;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('helios:reanalyse-pes-acquit')
            ->setDescription(
                "Réanalyse le PES Acquit d'une transaction"
            )
            ->addArgument(
                'transaction-id',
                InputArgument::REQUIRED,
                "La transaction_id dont le PES Acquit va être réanalysé"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $transaction_id = $input->getArgument('transaction-id');

        $path = $this->pesAcquitCloudStorage->getPath($transaction_id);
        if (empty($path)) {
            echo "[$transaction_id] Path vide, ignoré\n";
            return 0;
        }
        $filename = basename($path);
        $destination = $this->helios_ftp_response_tmp_local_path . "/$filename";
        echo "[$transaction_id] Copie de $path vers $destination\n";

        copy($path, $destination);
        $this->workerScript->putJobByClassName(HeliosAnalyseFichierRecuWorker::class, $filename);

        return 0;
    }
}
