<?php

namespace S2low\Services\Helios\DGFiPConnection;

use UnexpectedValueException;

/**
 * Modes de connection disponibles
 * Permet d'obtenir à partir du mode de connection utilisé (SIMULATEUR, GATEWAY, PASSTRANS_SFTP ... )
 *  1/ le protocole de connection utilisé (FTP, FTPS, SFTP )
 *  2/ les spécificités du mode de connection
 *      * Le simulateur a besoin qu'on lui spécifie de détruire les fichiers après téléchargement
 *      * les protocoles d'envoi de raw command diffèrent selon les modes de connexion
 *      * ...
 */

class DGFiPConnectionMode
{
    public const  SIMULATEUR = 'SIMULATEUR';
    public const GATEWAY = 'GATEWAY';
    public const PASSTRANS_SFTP = 'PASSTRANS_SFTP';
    public const PASSTRANS_FTPS = 'PASSTRANS_FTPS';
    private string $mode;

    /**
     * @param string $mode
     */
    public function __construct(string $mode)
    {
        if (!$this->isAvailable($mode)) {
            throw new UnexpectedValueException("Mode de connection DGFiP inconnu : $mode");
        }
        $this->mode = $mode;
    }

    /**
     * Le mode de connection demandé est-il implémenté ?
     * @param $mode
     * @return bool
     */
    public function isAvailable($mode): bool
    {
        return in_array(
            $mode,
            [self::SIMULATEUR,self::GATEWAY,self::PASSTRANS_FTPS,self::PASSTRANS_SFTP]
        );
    }

    /**
     * @return Protocol
     */
    public function getProtocol(): Protocol
    {
        if (in_array($this->mode, [ self::GATEWAY,self::SIMULATEUR])) {
            return new Protocol(Protocol::FTP);
        }
        if ($this->mode === self::PASSTRANS_FTPS) {
            return new Protocol(Protocol::FTPS);
        }
        if ($this->mode === self::PASSTRANS_SFTP) {
            return new Protocol(Protocol::SFTP);
        }

        throw new UnexpectedValueException('Protocole non implémenté');
    }

    /**
     * @return bool
     */
    public function isUsePasstransFTPS(): bool
    {
        return $this->mode === self::PASSTRANS_FTPS;
    }

    /**
     * Génère une chaine d'avertissement pour le mode démo
     * @return string
     */
    public function getModeDemoWarning(): string
    {
        if ($this->mode === self::SIMULATEUR) {
            return '[MODE DEMO]';
        }
        return '';
    }

    /**
     * @return string
     */
    public function getCurrentDirectory(): string
    {
        //Sur un serveur normal, c'est . par contre sur le site de la DGFip , c'est ./
        if ($this->mode === self::SIMULATEUR) {
            return '.';
        }
        return './';
    }

    /**
     * @return bool
     */
    public function getDeleteAfterDownload(): bool
    {
        // Sur les ftp de simulation, s2low doit demander lui-même la suppression des fichiers
        // après télécharement
        if ($this->mode === self::SIMULATEUR) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getCheckFtpRawCommandsReturn(): bool
    {
        // Sur les ftp de simulation, s2low ne vérifie pas le retour des raw commands
        if ($this->mode === self::SIMULATEUR) {
            return false;
        }
        return true;
    }
}
