<?php

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;
use S2lowLegacy\Class\S2lowLogger;

/**
 *
 */
class DGFiPConnection
{
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnector
     */
    private DGFiPConnector $serverProtocol;
    private string $sending_destination;
    private string $response_server_path;
    /**
     * @var S2lowLogger
     */
    private S2lowLogger $logger;
    private string $pAppli;

    /**
     * @param S2lowLogger $logger
     * @param DGFiPConnector $serverProtocolConfiguration
     * @param string $response_server_path
     * @param string $sending_destination
     * @param string $helios_ftp_p_appli
     */
    public function __construct(
        S2lowLogger $logger,
        DGFiPConnector $serverProtocolConfiguration,
        string $response_server_path,
        string $sending_destination,
        string $helios_ftp_p_appli
    ) {
        $this->logger = $logger;
        $this->response_server_path = $response_server_path;
        $this->sending_destination = $sending_destination;
        $this->serverProtocol = $serverProtocolConfiguration;
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
     */
    public function connect(): void
    {
        $this->logger->info("Connection à {$this->getURL()}");
        $this->serverProtocol->connect();
        $this->logger->info('Connecté');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getFileNames(): array
    {
        $this->logger->info("Remote_path : $this->response_server_path");
        $all_file = $this->serverProtocol->getFileNames($this->response_server_path);

        $message = 'Il y a ' . count($all_file) . " fichiers en attente dans le repertoire distant $this->response_server_path...";
        $this->logger->info($message);

        return $all_file;
    }

    /**
     * @param $file
     * @param $local_path
     * @return bool
     * @throws Exception
     */
    public function retrieveFile($file, $local_path): bool
    {
        $tmp_file = $this->createTmpFile($local_path);
        $ftp_get_result =  $this->serverProtocol->retrieveFile($tmp_file, $file);

        $rename_result = rename($tmp_file, "$local_path/$file");
        if (!$rename_result) {
            throw new Exception("Impossible de déplacer le fichier $tmp_file vers $local_path/$file");
        }

        $this->serverProtocol->deleteIfNeedBe($file);

        return $ftp_get_result;
    }

    /**
     * @param $localPath
     * @return string
     * @throws Exception
     */
    private function createTmpFile($localPath): string
    {
        $tmp_file = sys_get_temp_dir() . '/s2low_helios_ftp_retrieve_' . mt_rand(0, mt_getrandmax());

        if (disk_free_space($localPath) < 1000000 || disk_free_space(dirname($tmp_file)) < 1000000) {
            throw new Exception("Il ne reste pas assez d'espace sur le disque pour créer le fichier dans $localPath !");
        }
        return $tmp_file;
    }

    /**
     */
    public function disconnect(): void
    {
        $this->serverProtocol->close();
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
}
