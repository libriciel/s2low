<?php

namespace S2low\Services\Helios\DGFiPConnection;

use Exception;

/**
 *  Stocke un protocole parmi FTP, FTPS, SFTP utilisé pour la connection à un serveur de la DGFiP ou un simulateur
 */
class Protocol
{
    public const FTP = 'FTP';
    public const FTPS = 'FTPS';
    public const SFTP = 'SFTP';

    private const AVAILABLE_PROTOCOLS = [self::SFTP, self::FTPS,self::FTP];
    private string $protocol;

    /**
     * @param string $protocol  Une valeur parmi FTP, FTPS, SFTP
     * @throws Exception
     */
    public function __construct(string $protocol)
    {
        if (!in_array($protocol, self::AVAILABLE_PROTOCOLS)) {
            throw new Exception("Protocole inconnu : $protocol");
        }
        $this->protocol = $protocol;
    }

    /**
     * Vérifie que le protocole peut être implémenté par les fonctions ftp standard de php
     * @return bool
     */
    public function usesFTPConnection(): bool
    {
        return in_array($this->protocol, [self::FTPS, self::FTP]);
    }

    /**
     * @return bool
     */
    public function isFTPS(): bool
    {
        return $this->protocol === self::FTPS;
    }

    /**
     * Renvoie une représentation du protocole
     * @return string
     */
    public function __toString()
    {
        return $this->protocol;
    }
}
