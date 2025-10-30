<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class HeliosExport
{
    private $s2lowLogger;
    private $authoritySQL;
    private $heliosTransactionsSQL;

    public function __construct(
        LoggerInterface $s2lowLogger,
        AuthoritySQL $authoritySQL,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        #[Autowire(service: 'app.localFileResolver.pes_aller')]
        private readonly LocalFileResolver $pesAllerResolver,
        #[Autowire(service: 'app.store.file.pes_aller')]
        private readonly CloudFileStorageInterface $cloudPesAllerStorage,
        #[Autowire(service: 'app.localFileResolver.pes_acquit')]
        private readonly LocalFileResolver $pesAcquitResolver,
        #[Autowire(service: 'app.store.file.pes_acquit')]
        private readonly CloudFileStorageInterface $cloudPesAcquitStorage
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->authoritySQL = $authoritySQL;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
    }

    /**
     * @param int $authority_id
     * @param string $output_directory
     * @param int $min_transaction_id
     * @param int $max_trasaction_id
     * @throws Exception
     */
    public function export(
        int $authority_id,
        string $output_directory,
        int $min_transaction_id = 0,
        int $max_trasaction_id = ActesTransactionsSQL::MAX_ID
    ): void {

        $this->s2lowLogger->info(
            sprintf(
                "Export des PES ALLER et PES Acquit de la collectivité %d vers %s (min_transaction_id=%d, max_transaction_id=%d)",
                $authority_id,
                $output_directory,
                $min_transaction_id,
                $max_trasaction_id
            )
        );

        $authority_info = $this->checkAuthority($authority_id);
        $this->s2lowLogger->info(sprintf("Traitement des actes de la collectivité %s", $authority_info['name']));

        $this->checkOutputDirectory($output_directory);

        $transactions_list = $this->heliosTransactionsSQL->getAllForExport($authority_id, $min_transaction_id, $max_trasaction_id);

        if (! $transactions_list) {
            $this->s2lowLogger->info("Aucune transaction ne correspond aux critères");
            return;
        }

        $this->s2lowLogger->info(count($transactions_list) . " transaction(s) trouvée(s)");


        foreach ($transactions_list as $transaction_info) {
            $this->exportOneTransaction($transaction_info, $output_directory);
        }
    }

    /**
     * @param int $authority_id
     * @return array
     * @throws UnrecoverableException
     */
    private function checkAuthority(int $authority_id): array
    {
        $authority_info = $this->authoritySQL->getInfo($authority_id);

        if (! $authority_info) {
            throw new UnrecoverableException("La collectivité $authority_id n'existe pas");
        }
        return $authority_info;
    }


    /**
     * @param string $output_directory
     * @throws UnrecoverableException
     */
    private function checkOutputDirectory(string $output_directory): void
    {
        $filesystem = new Filesystem();
        if (
            ! $filesystem->exists($output_directory) ||
            ! is_dir($output_directory) ||
            ! is_writable($output_directory)
        ) {
            throw new UnrecoverableException(
                "Le répertoire $output_directory n'existe pas ou n'est pas accessible en écriture"
            );
        }
    }

    /**
     * @param $transaction_info
     * @param $output_directory
     * @throws Exception
     */
    private function exportOneTransaction($transaction_info, $output_directory): void
    {
        $this->s2lowLogger->info("Export de la transaction #ID {$transaction_info['id']}");

        $directory_name = $transaction_info['id'];

        $filesystem = new Filesystem();
        $filesystem->mkdir($output_directory . "/" . $directory_name);

        $pes_aller_path = $this->pesAllerResolver->getFullPath($transaction_info['id']);
        $this->cloudPesAllerStorage->downloadFileFromCloud($transaction_info['id']);
        $pes_aller_destination = $output_directory . "/$directory_name/{$transaction_info['filename']}";
        $filesystem->copy($pes_aller_path, $pes_aller_destination);
        $this->s2lowLogger->debug("[COPIE OK] $pes_aller_path -> $pes_aller_destination");

        if ($transaction_info['acquit_filename']) {
            $this->cloudPesAcquitStorage->downloadFileFromCloud($transaction_info['id']);
            $pes_acquit_path = $this->pesAcquitResolver->getFullPath($transaction_info['id']);

            $pes_acquit_destintation = $output_directory . "/$directory_name/{$transaction_info['acquit_filename']}";
            $filesystem->copy($pes_acquit_path, $pes_acquit_destintation);
            $this->s2lowLogger->debug("[COPIE OK] $pes_acquit_path -> $pes_acquit_destintation");
        }
    }
}
