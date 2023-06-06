<?php

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;

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
     */
    public function connect(): void
    {
        $this->activeSFTPConnection->connect();
    }

    /**
     * Retourne les fichiers disponibles sur un répertoire du serveur
     * @param string $remote_path Chemin du répertoire
     * @return array
     * @throws \Exception
     */
    public function getFileNames(string $remote_path): array
    {
        if (!$this->activeSFTPConnection->chdir($remote_path)) {
            throw new Exception("Impossible d'aller sur le répertoire distant $remote_path");
        }
        return $this->activeSFTPConnection->nlist('./');
    }

    /**
     * Télécharge un fichier
     * @param string $tmp_file  chemin du fichier local
     * @param string $file chemin du fichier distant
     * @return bool
     */
    public function retrieveFile(string $tmp_file, string $file): bool
    {
        try {
            $this->activeSFTPConnection->get($tmp_file, $file);
        } catch (Exception $exception) {
            echo $exception->getMessage();
            return false;
        }
        return true;
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
        //$filename = basename($file_to_send);
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
}
