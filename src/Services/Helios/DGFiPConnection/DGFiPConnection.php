<?php

declare(strict_types=1);

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;
use Psr\Log\LoggerInterface;
use S2low\Exceptions\ConnectionFailedException;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryManagerFactory;
use SplFileInfo;

/**
 *
 */
class DGFiPConnection
{
    /**
     * @var DGFiPConnector
     */
    private DGFiPConnector $serverProtocol;
    private string $sending_destination;
    private string $response_server_path;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    private string $pAppli;
    /**
     * @var DirectoryManagerFactory
     */
    private DirectoryManagerFactory $directoryManagerFactory;

    /**
     * @param LoggerInterface $logger
     * @param DGFiPConnector $serverProtocolConfiguration
     * @param DirectoryManagerFactory $directoryManagerFactory
     * @param string $response_server_path
     * @param string $sending_destination
     * @param string $helios_ftp_p_appli
     */
    public function __construct(
        LoggerInterface $logger,
        DGFiPConnector $serverProtocolConfiguration,
        DirectoryManagerFactory $directoryManagerFactory,
        string $response_server_path,
        string $sending_destination,
        string $helios_ftp_p_appli
    ) {
        $this->logger = $logger;
        $this->response_server_path = $response_server_path;
        $this->sending_destination = $sending_destination;
        $this->serverProtocol = $serverProtocolConfiguration;
        $this->directoryManagerFactory = $directoryManagerFactory;
        $this->pAppli = $helios_ftp_p_appli;
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return $this->serverProtocol->getURL();
    }

    /**
     * @return void
     * @throws ConnectionFailedException
     */
    public function connect(): void
    {
        $this->logger->info("Connection à {$this->getURLWithoutCredentials()}");
        $this->serverProtocol->connect();
        $this->logger->info('Connecté');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFileNames(): array
    {
        $all_file = $this->serverProtocol->getFileNames();

        $nbFiles = count($all_file);
        $message = "Il y a $nbFiles fichiers en attente dans le repertoire distant $this->response_server_path...";
        $this->logger->info($message);

        return $all_file;
    }

    /**
     * @param $fileNameOnFTP
     * @param $local_path
     * @param $helios_responses_error_path
     * @param string $tmp_path
     * @return void
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     * @throws Exception
     */
    public function retrieveFile($fileNameOnFTP, $local_path, $helios_responses_error_path, string $tmp_path): void
    {
        $tmpDirectoryManager = $this->directoryManagerFactory->get($tmp_path);
        $tmpDirectoryManager->check();
        $localDirectoryManager = $this->directoryManagerFactory->get($local_path);
        $localDirectoryManager->check();

        $tmp_file = $tmpDirectoryManager->getTmpFile('/s2low_helios_ftp_retrieve_');

        $this->retrieveToTmpFile($tmp_file, $fileNameOnFTP);

        if ($localDirectoryManager->fileWithOutputNameExistsAndHasSameContent($tmp_file, $fileNameOnFTP)) {
            $this->logger->error("Fichier $fileNameOnFTP déjà téléchargé");
            unlink($tmp_file->getPathname());
            return;     //Rien d'autre à faire pour ce fichier
        }
        try {
            $localDirectoryManager->moveFileInDir($tmp_file, $fileNameOnFTP);
            return; // Le fichier est rapatrié, My job here is done
        } catch (Exception $exception) {
            $this->logger->error(
                "Impossible de déplacer $tmp_file vers $local_path/$fileNameOnFTP :" . $exception->getMessage()
            );
        }
        // On est du coup dans un cas d'erreur ou le fichier a été récupéré depuis le FTP, mais n'a pas pu être copié
        // dans le local path ...
        $errorDirectoryManager = $this->directoryManagerFactory->get($helios_responses_error_path);
        $errorDirectoryManager->check();

        $errorDirectoryManager->moveFileInDirWithRename($tmp_file, $fileNameOnFTP);
    }

    /**
     */
    public function disconnect(): void
    {
        $this->serverProtocol->close();
        $this->logger->info('Déconnecté');
    }

    /**
     * Envoie un fichier sur une connection existante
     * @param bool|string $p_dest
     * @param string $p_msg
     * @param string $file_to_send
     * @return void
     */
    public function sendOneFileWithProperties(bool|string $p_dest, string $p_msg, string $file_to_send): void
    {
        $this->serverProtocol->sendOneFileWithProperties(
            $p_dest,
            $p_msg,
            $this->pAppli,
            $this->sending_destination,
            $file_to_send
        );
    }

    /**
     * Envoie un fichier en se connectant et se déconnectant
     * @param string $p_dest
     * @param string $p_msg
     * @param string $file_to_send
     */
    public function sendFileOnUniqueConnection(string $p_dest, string $p_msg, string $file_to_send): void
    {
        $this->connect();
        $this->sendOneFileWithProperties($p_dest, $p_msg, $file_to_send);
        $this->disconnect();
    }

    /**
     * @param SplFileInfo $tmp_file
     * @param $file
     * @return void
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     * @throws Exception
     */
    public function retrieveToTmpFile(SplFileInfo $tmp_file, $file): void
    {
        $this->serverProtocol->retrieveFile($tmp_file->getPathname(), $file);

        if (!file_exists($tmp_file->getPathname())) {
            throw new Exception(
                "Erreur lors de la récupération de $file vers $tmp_file : fichier non existant"
            );
        }

        if (filesize($tmp_file->getPathname()) == 0) {
            throw new Exception(
                "Erreur lors de la récupération de $file vers $tmp_file : fichier vide"
            );
        }
        $this->serverProtocol->deleteIfNeedBe($file);   // On pourrait détruire une fois qu'on est sûrs que le fichier
        // est copié dans un répertoire autre que tmp... Mais comme ce n'est utilisé que dans les protocoles de test,
        // on ne se donne pas cette peine.
    }

    private function getURLWithoutCredentials(): string
    {
        return preg_replace('#//(.*?)@#', '//HIDDEN_URL_FOR_SECURITY@', $this->getURL());
    }

    public function moveToReponsePath()
    {
        $this->logger->info("Remote_path : $this->response_server_path");
        $this->serverProtocol->chdir($this->response_server_path);
    }

    public function pwd(): string
    {
        return $this->serverProtocol->pwd();
    }
}
