<?php

namespace S2low\Services\Helios;

use Exception;
use S2low\Services\Helios\FTPConnection\ActiveConnection;
use S2low\Services\Helios\FTPConnection\ServerPathsConfiguration;
use S2low\Services\Helios\FTPConnection\ServerProtocolConfiguration;
use S2lowLegacy\Class\S2lowLogger;

class HeliosConnection
{
    /**
     * @var \S2low\Services\Helios\FTPConnection\ServerProtocolConfiguration
     */
    private ServerProtocolConfiguration $serverProtocol;
    private string $sending_destination;
    private string $response_server_path;
    /**
     * @var \S2lowLegacy\Class\S2lowLogger
     */
    private S2lowLogger $logger;
    /**
     * @var \S2low\Services\Helios\FTPConnection\ActiveConnection
     */
    private ActiveConnection $connection;

    public function __construct(
        ActiveConnection $connection,
        S2lowLogger $logger,
        ServerPathsConfiguration $serverPaths,
        ServerProtocolConfiguration $serverProtocolConfiguration
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->response_server_path = $serverPaths->getResponseServerPath();
        $this->sending_destination = $serverPaths->getSendingDestination();
        $this->serverProtocol = $serverProtocolConfiguration;
    }

    /**
     * @return array|false
     * @throws Exception
     */
    public function getFileNames()
    {
        $this->logger->info("Remote_path : $this->response_server_path");
        $all_file = $this->serverProtocol->getFileNames(
            $this->response_server_path,
            $this->connection
        );

        $this->logger->info("Il y a " . count($all_file) . " fichiers en attente dans le repertoire distant $this->response_server_path...");

        return $all_file;
    }

    /**
     * @param string $tmp_file
     * @param $file
     * @param $local_path
     * @return bool
     * @throws Exception
     */
    public function retrieveFile($file, $local_path): bool
    {
        $tmp_file = $this->createTmpFile($local_path);
        $ftp_get_result =  $this->serverProtocol->retrieveFile($tmp_file, $file, $this->connection);

        $rename_result = rename($tmp_file, "$local_path/$file");
        if (!$rename_result) {
            throw new Exception("Impossible de déplacer le fichier $tmp_file vers $local_path/$file");
        }

        $this->serverProtocol->deleteIfNeedBe($file, $this->connection);

        return $ftp_get_result;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createTmpFile($localPath): string
    {
        $tmp_file = sys_get_temp_dir() . "/s2low_helios_ftp_retrieve_" . mt_rand(0, mt_getrandmax());

        if (disk_free_space($localPath) < 1000000 || disk_free_space(dirname($tmp_file)) < 1000000) {
            throw new Exception("Il ne reste pas assez d'espace sur le disque pour créer le fichier dans $localPath !");
        }
        return $tmp_file;
    }

    /**
     */
    public function disconnect(): void
    {
        $this->connection->close();
    }

    public function sendOneFileWithProperties(bool|string $p_dest, string $p_msg, $pAppli, string $file_to_send)
    {
        $this->serverProtocol->sendOneFileWithProperties(
            $p_dest,
            $p_msg,
            $pAppli,
            $this->sending_destination,
            $file_to_send,
            $this->connection
        );
    }
}
