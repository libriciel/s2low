<?php

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;
use S2low\Exceptions\ConnectionFailedException;

/**
 *  Implémente une connection vers le serveur DGFiP utilisant le protocole SFTP
 */
class DGFiPConnectorOnSFTP implements DGFiPConnector
{
    /**
     * @var SFTPConnection
     */
    private SFTPConnection $activeSFTPConnection;

    /**
     * @param SFTPConnection $activeSFTPConnection
     */
    public function __construct(SFTPConnection $activeSFTPConnection)
    {
        $this->activeSFTPConnection = $activeSFTPConnection;
    }

    /**
     * Ouvre la connection
     * @return void
     * @throws Exception
     * @throws ConnectionFailedException
     */
    public function connect(): void
    {
        $this->activeSFTPConnection->connect();
    }

    /**
     * Retourne les fichiers disponibles sur un répertoire du serveur
     * @return array
     * @throws \Exception
     */
    public function getFileNames(): array
    {
        return $this->activeSFTPConnection->nlist('./');
    }

    /**
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     */
    public function retrieveFile(string $localFilePath, string $remoteFilePath): void
    {
            $this->activeSFTPConnection->get($localFilePath, $remoteFilePath);
    }

    /**
     * Ordonne de supprimer explicitement le fichier
     * @param $file
     * @return void
     */
    public function deleteIfNeedBe($file): void
    {
        // Aucun cas en SFTP nécessitant ça
    }

    /**
     * Ferme la connection
     * @return void
     */
    public function close(): void
    {
        $this->activeSFTPConnection->close();
    }

    /**
     * Envoie le fichier $file_to_send sur le répertoire distant $destinationDirectory
     * @param string $p_dest Destination (Poste comptable )
     * @param string $p_msg
     * @param string $pAppli Application (THELPES2, GHELPES2 ...)
     * @param string $destinationDirectory Répertoire distant
     * @param string $file_to_send Chemin du fichier à envoyer
     * @throws Exception
     */
    public function sendOneFileWithProperties(
        string $p_dest,
        string $p_msg,
        string $pAppli,
        string $destinationDirectory,
        string $file_to_send
    ): void {
        $hash = sha1_file($file_to_send);
        $passtransFileName = "$p_dest%%$pAppli%%$p_msg%%$hash";
        $this->activeSFTPConnection->put("$destinationDirectory/$passtransFileName", $file_to_send);
    }

    /**
     * Retourne l'URL de Connection
     * @return string
     */
    public function getURL(): string
    {
        return "{$this->activeSFTPConnection->getURL()}";
    }

    /**
     * @throws Exception
     */
    public function chdir(string $remote_path)
    {
        if (!$this->activeSFTPConnection->chdir($remote_path)) {
            throw new Exception("Impossible d'aller sur le répertoire distant $remote_path");
        }
    }
    public function pwd(): string
    {
        return $this->activeSFTPConnection->putwd();
    }
}
