<?php

namespace S2low\Services\Helios\FTPConnection;

use Exception;

class FTPServerProtocolConfiguration implements ServerProtocolConfiguration
{
    private string $currentDirectorySyntax;
    private bool $deleteAfterDownload;
    private mixed $modeDemo;
    private mixed $usePasstransFTPSProtocol;

    public function getDemoModeAsString(): string
    {
        return $this->modeDemo ? "[MODE DEMO]" : "";
    }

    public function __construct($modeDemo, $usePasstransFTPSProtocol)
    {
        $this->modeDemo = $modeDemo;
        $this->usePasstransFTPSProtocol = $usePasstransFTPSProtocol;
            //Attention, sur un serveur normal, c'est . par contre sur le site de la DGFip , c'est ./
        if ($modeDemo) {                     //TODO : vérifier que la config marche et est pertinente
            $this->currentDirectorySyntax = ".";
            $this->deleteAfterDownload = true;
        } else {
            $this->currentDirectorySyntax = "./";
            $this->deleteAfterDownload = false;
        }
    }

    public function checkRawCommand(?array $result, string $command): void
    {
        if ($this->modeDemo) {          //TODO : ajouter le mode demo
            return ;
        }
        if (!$result || ! preg_match("#^200#", $result[0])) {
            $message =  "[FAILED] Send FTP raw command\n$command\n********** RESULT *******\n";
            $message .= implode("\n", $result) . "\n";
            $message .=  "******** END RESULT ************\n";
            throw new Exception($message);
        }
    }

    public function getFileNames(string $remote_path, ActiveConnection $activeConnection): array
    {
        if (!$activeConnection->chdir($remote_path)) {
            throw new Exception("Impossible d'aller sur le répertoire distant $remote_path");
        }

        $all_file = $activeConnection->nlist(
            $this->currentDirectorySyntax
        );

        if ($all_file === false) {
            throw new Exception("Impossible de lister le contenu du répertoire distant $remote_path");
        }
        return $all_file;
    }

    public function retrieveFile(string $tmp_file, $file, ActiveConnection $activeConnection): bool
    {
        $ftp_get_result = $activeConnection->get($tmp_file, "$file", FTP_ASCII);
        if (!$ftp_get_result) {
            throw new Exception(
                "Impossible de récupérer le fichier $file pour le mettre sur $tmp_file sur le FTP"
            );
        }
        return $ftp_get_result;
    }

    public function deleteIfNeedBe($file, ActiveConnection $activeConnection): void
    {
        if ($this->deleteAfterDownload) {
            $activeConnection->delete($file);
        }
    }

    public function close(ActiveFTPorFTPSConnection $activeFTPorFTPSConnection)
    {
        $activeFTPorFTPSConnection->close();
    }

    private function sendRawCommand($command, ActiveConnection $activeConnection)
    {
        $this->checkRawCommand(
            $activeConnection->raw($command),
            $command
        );
    }

    private function configureFileProperties(
        string $p_dest,
        string $p_msg,
        $pAppli,
        ActiveConnection $activeConnection
    ): void {
        if ($this->usePasstransFTPSProtocol) {
            $commands = ["site meta P_DEST={$p_dest};P_APPLI={$pAppli};P_MSG=$p_msg"];
        } else {
            $commands = ["site P_DEST {$p_dest}","site P_APPLI {$pAppli}","site P_MSG $p_msg"];
        }
        foreach ($commands as $command) {
            $this->sendRawCommand($command, $activeConnection);
        }
    }

    /**
     * @param bool|string $p_dest
     * @param string $p_msg
     * @param $pAppli
     * @param $destinationDirectory
     * @param string $file_to_send
     * @param \S2low\Services\Helios\FTPConnection\ServerProtocolConfiguration $protocolConfiguration
     * @throws Exception
     */
    public function sendOneFileWithProperties(
        bool|string $p_dest,
        string $p_msg,
        $pAppli,
        $destinationDirectory,
        string $file_to_send,
        ActiveConnection $activeConnection
    ): void {
        $this->configureFileProperties($p_dest, $p_msg, $pAppli, $activeConnection);

        $result = $activeConnection->put(
            $destinationDirectory . basename($file_to_send),
            $file_to_send
        );
        if (! $result) {
            throw new Exception(
                "Erreur lors de l'envoi du fichier " . basename($file_to_send) . " vers le serveur FTP"
            );
        }
    }
}
