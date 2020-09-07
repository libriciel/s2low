<?php
class FTPHeliosSender {
    /** @var FTPService  */
    private $FTPConnection;
    private $pstMode;
    private $pAppli;

    public function __construct(FTPService $ftptemp, $helios_ftp_pst_mode,$helios_ftp_p_appli)
    {
        $this->FTPConnection = $ftptemp;
        $this->pstMode = $helios_ftp_pst_mode;
        $this->pAppli = $helios_ftp_p_appli;
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
        $this->FTPConnection->setPassiveMode(HELIOS_FTP_PASSIVE_MODE);
        if($this->pstMode){
            $command = "site meta P_DEST={$p_dest};P_APPLI={$this->pAppli};P_MSG=$p_msg";          // TODO : self::P_APPLI=THELPES2 const ??
            echo "$command\n";
            $this->FTPConnection->sendRawCommand($command, false/*HELIOS_SENDING_MODE_DEMO*/);
        } else {
            $ftp->sendRawCommand("site P_DEST {$p_dest}",HELIOS_SENDING_MODE_DEMO);
            $ftp->sendRawCommand("site P_APPLI {$this->pAppli}",HELIOS_SENDING_MODE_DEMO);  // TODO : self::P_APPLI=THELPES2 const ??
            $ftp->sendRawCommand("site P_MSG $p_msg",HELIOS_SENDING_MODE_DEMO);

        }
        $this->FTPConnection->sendOneFile("depot/"/*HELIOS_SENDING_DESTINATION*/, $file_to_send);
        $this->FTPConnection->disconnect();
    }
}