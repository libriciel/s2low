<?php
class FTPFileSender {

    /** @var FTPConnection  */
    private $FTPConnection;

    public function __construct(FTPConnection $ftptemp)
    {
        $this->FTPConnection = $ftptemp;
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
        $command = "site meta P_DEST={$p_dest};P_APPLI=THELPES2;P_MSG=$p_msg";
        echo "$command\n";
        $this->FTPConnection->sendRawCommand($command, false/*HELIOS_SENDING_MODE_DEMO*/);
        $this->FTPConnection->sendOneFile("depot/"/*HELIOS_SENDING_DESTINATION*/, $file_to_send);
        $this->FTPConnection->disconnect();
    }
}