<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class HeliosExport
{
    private $s2lowLogger;
    private $authoritySQL;
    private $heliosTransactionsSQL;
    private $pesAllerRetriever;
    private $helios_responses_root;

    public function __construct(
        LoggerInterface $s2lowLogger,
        AuthoritySQL $authoritySQL,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        PesAllerRetriever $pesAllerRetriever,
        $helios_responses_root,
        private PESAcquitCloudStorage $pesAcquitCloudStorage
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->authoritySQL = $authoritySQL;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->pesAllerRetriever = $pesAllerRetriever;
        $this->helios_responses_root = $helios_responses_root;
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
     */
    private function exportOneTransaction($transaction_info, $output_directory): void
    {
        $this->s2lowLogger->info("Export de la transaction #ID {$transaction_info['id']}");

        $directory_name = $transaction_info['id'];

        $this->exportOneFile(
            $transaction_info['id'],
            fn() => $this->pesAllerRetriever->getPath($transaction_info['sha1']),
            $output_directory . "/$directory_name/{$transaction_info['filename']}"
        );

        if ($transaction_info['acquit_filename']) {
            $this->exportOneFile(
                $transaction_info['id'],
                fn() => $this->pesAcquitCloudStorage->getPath($transaction_info['id']),
                $output_directory . "/$directory_name/{$transaction_info['acquit_filename']}"
            );
        }
    }

    /**
     * Un fichier introuvable (ni sur le serveur, ni dans le cloud) ne doit pas interrompre l'export :
     * il est signalé dans les logs et l'export continue avec les fichiers suivants.
     *
     * PesAllerRetriever::getPath() et CloudStorage::getPath() renvoient false lorsqu'ils n'ont pas pu
     * fournir le fichier, et CloudStorage::getPath() lève une exception lorsque le fichier n'est ni
     * sur le disque ni récupérable dans le cloud.
     *
     * @param $transaction_id
     * @param callable $getSourcePath renvoie le chemin du fichier à copier, ou false
     * @param string $destination_path
     */
    private function exportOneFile($transaction_id, callable $getSourcePath, string $destination_path): void
    {
        $nom_fichier = basename($destination_path);

        try {
            $source_path = $getSourcePath();
        } catch (Exception $e) {
            $this->signaleUnFichierNonExporte($transaction_id, $nom_fichier, $e->getMessage());
            return;
        }

        if (! $source_path) {
            $this->signaleUnFichierNonExporte(
                $transaction_id,
                $nom_fichier,
                "fichier introuvable sur le serveur comme dans le cloud"
            );
            return;
        }

        try {
            (new Filesystem())->copy($source_path, $destination_path);
        } catch (IOExceptionInterface $e) {
            $this->signaleUnFichierNonExporte($transaction_id, $nom_fichier, $e->getMessage());
            return;
        }

        $this->s2lowLogger->debug("[COPIE OK] $source_path -> $destination_path");
    }

    private function signaleUnFichierNonExporte($transaction_id, string $nom_fichier, string $raison): void
    {
        $this->s2lowLogger->warning(
            sprintf(
                "[COPIE KO] transaction #ID %s : %s n'a pas pu être exporté (%s)",
                $transaction_id,
                $nom_fichier,
                $raison
            )
        );
    }
}
