<?php

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;
use phpseclib3\Net\SFTP;

/**
 *  Implémentation d'une connection vers le serveur DGFiP utilisant le protocole FTP ou FTPS
 */
class DGFiPConnectorOnFTP implements DGFiPConnector
{
    private string $currentDirectorySyntax;
    private bool $deleteAfterDownload;
    private mixed $usePasstransFTPSProtocol;
    /**
     * @var FTPConnection
     */
    private FTPConnection $connection;
    private bool $checkFtpRawCommandsReturn;
    private string $modeDemoWarning;

    /**
     * @param string $currentDirectorySyntax
     * @param bool $deleteAfterDownload
     * @param bool $checkFtpRawCommandsReturn
     * @param bool $usePasstransFTPSProtocol
     * @param string $modeDemoWarning
     * @param FTPConnection $connection
     */
    public function __construct(
        string $currentDirectorySyntax,
        bool $deleteAfterDownload,
        bool $checkFtpRawCommandsReturn,
        bool $usePasstransFTPSProtocol,
        string $modeDemoWarning,
        FTPConnection $connection
    ) {
        $this->currentDirectorySyntax = $currentDirectorySyntax;
        $this->deleteAfterDownload = $deleteAfterDownload;
        $this->checkFtpRawCommandsReturn = $checkFtpRawCommandsReturn;
        $this->usePasstransFTPSProtocol = $usePasstransFTPSProtocol;
        $this->modeDemoWarning = $modeDemoWarning;
        $this->connection = $connection;
    }

    /**
     * @return \S2low\Services\Helios\DGFiPConnection\FTPConnection
     * @throws \Exception
     */
    public function getConnection(): FTPConnection
    {
        if (!$this->connection->isConnected()) {
            throw new Exception('Aucune connection configurée');
        }
        return $this->connection;
    }

    /**
     * @param array|null $result
     * @param string $command
     * @return void
     * @throws \Exception
     */
    public function checkRawCommand(?array $result, string $command): void
    {
        if (! $this->checkFtpRawCommandsReturn) {
            return ;
        }
        if (!$result || !str_starts_with($result[0], '200')) {
            $message =  "[FAILED] Send FTP raw command\n$command\n********** RESULT *******\n";
            $message .= implode("\n", $result) . "\n";
            $message .=  "******** END RESULT ************\n";
            throw new Exception($message);
        }
    }

    /**
     * Retourne les fichiers disponibles sur un répertoire du serveur
     * @return array
     * @throws \Exception
     */
    public function getFileNames(): array
    {
        $all_file = $this->getConnection()->nlist(
            $this->currentDirectorySyntax
        );

        if ($all_file === false) {
            throw new Exception("Impossible de lister le contenu du répertoire distant");
        }
        return $all_file;
    }

    /**
     * @throws \S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException
     * @throws \Exception
     */
    public function retrieveFile(string $localFilePath, string $remoteFilePath): void
    {
        $ftp_get_result = $this->getConnection()->get($localFilePath, "$remoteFilePath");
        if (!$ftp_get_result) {
            $errorMessage = var_export(error_get_last(), true);
            throw new FTPFileRetrieveException(
                "Impossible de transférer le fichier distant $remoteFilePath vers $localFilePath : " . $errorMessage
            );
        }
    }

    /**
     * Ordonne de supprimer explicitement le fichier
     * @param $file
     * @return void
     * @throws Exception
     */
    public function deleteIfNeedBe($file): void
    {
        if ($this->deleteAfterDownload) {
            $this->getConnection()->delete($file);
        }
    }

    /**
     * Ferme la connection
     * @return void
     * @throws \Exception
     */
    public function close(): void
    {
        $this->getConnection()->close();
    }

    /**
     * @throws Exception
     */
    private function sendRawCommand($command): void
    {
        $this->checkRawCommand(
            $this->getConnection()->raw($command),
            $command
        );
    }

    /**
     * @param string $p_dest
     * @param string $p_msg
     * @param $pAppli
     * @return void
     * @throws Exception
     */
    private function configureFileProperties(
        string $p_dest,
        string $p_msg,
        $pAppli
    ): void {
        if ($this->usePasstransFTPSProtocol) {
            $commands = ["site meta P_DEST=$p_dest;P_APPLI=$pAppli;P_MSG=$p_msg"];
        } else {
            $commands = ["site P_DEST $p_dest","site P_APPLI $pAppli","site P_MSG $p_msg"];
        }
        foreach ($commands as $command) {
            $this->sendRawCommand($command);
        }
    }

    /**
     * @param string $p_dest
     * @param string $p_msg
     * @param string $pAppli
     * @param string $destinationDirectory
     * @param string $file_to_send
     * @throws \Exception
     */
    public function sendOneFileWithProperties(
        string $p_dest,
        string $p_msg,
        string $pAppli,
        string $destinationDirectory,
        string $file_to_send
    ): void {
        $this->configureFileProperties($p_dest, $p_msg, $pAppli);

        $result = $this->getConnection()->put(
            $destinationDirectory . basename($file_to_send),
            $file_to_send
        );
        if (! $result) {
            throw new Exception(
                'Erreur lors de l\'envoi du fichier ' . basename($file_to_send) . ' vers le serveur FTP'
            );
        }
    }

    /**
     * Ouvre la connection
     * @return void
     * @throws Exception
     */
    public function connect(): void
    {
        $this->connection->connect();
    }

    /**
     * Retourne l'URL de Connection
     * @return string
     * @throws \Exception
     */
    public function getURL(): string
    {
        return "{$this->connection->getURL()}.$this->modeDemoWarning";
    }

    public function chdir(string $remote_path): void
    {
        if (!$this->getConnection()->chdir($remote_path)) {
            throw new Exception("Impossible d'aller sur le répertoire distant $remote_path");
        }
    }

    public function pwd(): string
    {
        return $this->getConnection()->putwd();
    }
}
