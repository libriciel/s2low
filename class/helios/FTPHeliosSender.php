<?php

namespace S2lowLegacy\Class\helios;

use Exception;

class FTPHeliosSender
{
    /** @var FTPService  */
    private $FTPConnection;
    private $isPstMode;
    private $pAppli;
    private $destinationDirectory;

    public function __construct(
        FTPService $ftptemp,
        $helios_ftp_passtrans_mode,
        $helios_ftp_p_appli,
        $helios_sending_destination
    ) {
        $this->FTPConnection = $ftptemp;
        $this->isPstMode = $helios_ftp_passtrans_mode;
        $this->pAppli = $helios_ftp_p_appli;
        $this->destinationDirectory = $helios_sending_destination;
    }

    /**
     * @param bool $p_dest
     * @param string $p_msg
     * @param string $file_to_send
     * @throws Exception
     */
    public function sendFile(string $p_dest, string $p_msg, string $file_to_send): void
    {
        $this->FTPConnection->connect();
        $this->configureFileProperties($p_dest, $p_msg);
        $this->FTPConnection->sendOneFile($this->destinationDirectory, $file_to_send);
        $this->FTPConnection->disconnect();
    }

    /**
     * @param bool $p_dest Code destinataire
     * @param string $p_msg de la forme CodFich#CodColl#IdPost#CodBud
     * @throws Exception
     */
    private function configureFileProperties(string $p_dest, string $p_msg): void
    {
        if ($this->isPstMode) {
            $commands = ["site meta P_DEST={$p_dest};P_APPLI={$this->pAppli};P_MSG=$p_msg"];
        } else {
            $commands = ["site P_DEST {$p_dest}","site P_APPLI {$this->pAppli}","site P_MSG $p_msg"];
        }
        foreach ($commands as $command) {
            $this->FTPConnection->sendRawCommand($command);
        }
    }
}
