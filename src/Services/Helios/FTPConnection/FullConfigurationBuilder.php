<?php

namespace S2low\Services\Helios\FTPConnection;

class FullConfigurationBuilder
{
    public function generateConfiguration($server, $port, $login, $password, $pst_mode, $passiveMode, $sendingDestination, $responseServerPath)
    {
        $modeDemo = false;
        $usePasstransFTPSProtocol = false;
        switch ($pst_mode) {
            case 'FTP_SIMULATEUR':
                $modeDemo = true;
                $protocol = "ftp";
                break;
            case 'FTP_GATEWAY':
                $protocol = "ftp";
                break;
            case 'SFTP_PASSTRANS':
                $protocol = "sftp";
                break;
            case 'FTPS_PASSTRANS':
                $protocol = "ftps";
                $usePasstransFTPSProtocol = true;
                break;
            default:
                throw new \UnexpectedValueException("helios_ftp_passtrans_mode inconnu : {$pst_mode}");
        }

        $connectionConfiguration = new ConnectionConfiguration($server, $port, $login, $password, $passiveMode, $protocol);

        if (in_array($protocol, ["ftp", "ftps"])) {
            $serverProtocolConfiguration = new FTPServerProtocolConfiguration(
                $modeDemo,
                $usePasstransFTPSProtocol
            );
        } else {
            $serverProtocolConfiguration = new SFTPServerProtocolConfiguration();
        }

        $serverPathsConfiguration = new ServerPathsConfiguration($responseServerPath, $sendingDestination);
        return new FullConfiguration($connectionConfiguration, $serverProtocolConfiguration, $serverPathsConfiguration);
    }
}
