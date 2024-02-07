<?php

namespace PHPUnit;

use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Lib\ObjectInstancier;

trait RgsConnexionTestTrait
{
    public function setRGS2stars()
    {
        $server['SSL_CLIENT_VERIFY'] = "SUCCESS";
        $server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__ . "/controller/fixtures/contact@example.org.pem");
        $server['SSL_CLIENT_CERT_CHAIN_0'] = file_get_contents(__DIR__ . "/controller/fixtures/AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem");

        $rgsConnexion = $this->getObjectInstancier()->get(RgsConnexion::class);

        $rgsConnexion->setServerGlobal($server);
        $rgsConnexion->setRgsValidCaPath("/etc/s2low/ssl/validca/");
    }

    /**
     * @return ObjectInstancier
     */
    abstract public function getObjectInstancier();
}
