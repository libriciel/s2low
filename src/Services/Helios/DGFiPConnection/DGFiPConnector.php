<?php

declare(strict_types=1);

namespace S2low\Services\Helios\DGFiPConnection;

/**
 * Abstracts the particular connection protocol to the DGFiP
 */
interface DGFiPConnector
{
    /**
     * Retourne les fichiers disponibles sur un répertoire du serveur
     * @param string $remote_path Chemin du répertoire
     * @return array
     */
    public function getFileNames(string $remote_path): array;

    /**
     * Télécharge un fichier
     * @param string $tmp_file chemin du fichier local
     * @param string $file chemin du fichier distant
     * @return void
     * @throws \Exception
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     */
    public function retrieveFile(string $tmp_file, string $file): void;

    /**
     * Ordonne de supprimer explicitement le fichier
     * @param $file
     * @return void
     */
    public function deleteIfNeedBe($file): void;

    /**
     * Ferme la connection
     * @return void
     */
    public function close();

    /**
     * Envoie le fichier $file_to_send sur le répertoire distant $destinationDirectory
     * @param string $p_dest                    Destination (Poste comptable )
     * @param string $p_msg
     * @param string $pAppli                    Application (THELPES2, GHELPES2 ...)
     * @param  string $destinationDirectory     Répertoire distant
     * @param string $file_to_send              Chemin du fichier à envoyer
     */
    public function sendOneFileWithProperties(
        string $p_dest,
        string $p_msg,
        string $pAppli,
        string $destinationDirectory,
        string $file_to_send
    ): void;

    /**
     * Ouvre la connection
     * @return void
     */
    public function connect();

    /**
     * Retourne l'URL de Connection
     * @return string
     */
    public function getURL(): string;
}
