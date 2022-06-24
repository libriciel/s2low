<?php

trait RgsConnexionTestTrait
{
    public function setRGS2stars()
    {
        $server['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__ . "/controller/fixtures/contact@example.org.pem");
        $server['SSL_CLIENT_CERT_CHAIN_0'] = file_get_contents(__DIR__ . "/controller/fixtures/ca_users_chaine.pem");

        $rgsConnexion = $this->getObjectInstancier()->get(RgsConnexion::class);

        $rgsConnexion->setServerGlobal($server);
        $rgsConnexion->setRgsValidCaPath("/etc/s2low/ssl/validca/");
    }

    /**
     * @return ObjectInstancier
     */
    abstract public function getObjectInstancier();
}
