<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\RgsCertificate;

class RgsConnexion
{
    private $last_message;
    private $openssl_path;
    private $rgs_validca_path;
    private $server_global;

    public function __construct()
    {
        $this->setOpenSSLPath(OPENSSL_PATH);
        $this->setRgsValidCaPath(RGS_VALIDCA_PATH);
        $this->setServerGlobal($_SERVER);
    }

    public function getClientCertChain()
    {
        $i = 0;
        $clientCertChain = '';
        while (isset($this->server_global['SSL_CLIENT_CERT_CHAIN_' . $i])) {
            $clientCertChain .= $this->server_global['SSL_CLIENT_CERT_CHAIN_' . $i];
            $clientCertChain .= "\n";
            $i++;
        }
        return $clientCertChain;
    }
    public function setOpenSSLPath($openssl_path)
    {
        $this->openssl_path = $openssl_path;
    }

    public function setRgsValidCaPath($rgs_validca_path)
    {
        $this->rgs_validca_path = $rgs_validca_path;
    }

    public function setServerGlobal($server_global)
    {
        $this->server_global = $server_global;
    }

    public function getLastMessage()
    {
        return $this->last_message;
    }

    public function isRgsConnexion()
    {
        if (empty($this->server_global['SSL_CLIENT_VERIFY'])) {
            $this->last_message = "Impossible de vérifier la connexion HTTPS";
            return false;
        }

        if ($this->server_global['SSL_CLIENT_VERIFY'] != "SUCCESS") {
            $this->last_message = "La connexion HTTPS n'est pas opérationnel";
            return false;
        }

        $rgsCertificate = new RgsCertificate($this->openssl_path, $this->rgs_validca_path);
        $result = $rgsCertificate->isRgsCertificate($this->server_global['SSL_CLIENT_CERT'], $this->getClientCertChain());
        if (! $result) {
            $this->last_message = $rgsCertificate->getLastMessage();
        }
        return $result;
    }
}
