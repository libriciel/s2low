<?php

use S2lowLegacy\Class\User;

class UserTest extends S2lowTestCase
{
    public function testGetIdFromCertData()
    {
        $user = new User();
        $ids = $user->getIdFromCertData('Q1pUbEb5DK53BkYf0arDl/3zl5U=');
        $this->assertEquals(1, $ids[0]);
    }

    public function testgetNbUserWithMyCertificate()
    {
        $user = new User();
        $user->setId(2);
        $user->init();
        $this->assertEquals(2, $user->getNbUserWithMyCertificate());
    }

    public function testGetCertificateInfo()
    {
        $user = new User();
        $user->setId(2);
        $user->init();
        $info = $user->getCertificateInfo();
        $this->assertEquals('hash_adullact', $info['certificate_hash']);
    }

    public function testGetDn()
    {
        $user = new User();
        $certificate = [
            'C' => "FR",
            'O' => "CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE",
            'OU' => [
                "CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE",
                "0002 28440002500011",
                "Systèmes d'information",
            ],
            'organizationIdentifier' => "NTRFR-28440002500011",
            'L' => "NANTES CEDEX 2",
            'CN' => "iparapheur.cdg44.fr",
            'serialNumber' => "0001"
            ];

        $this->assertEquals(
            "/C=FR/O=CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE/OU=CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE/OU=0002 28440002500011/OU=Systèmes d'information/organizationIdentifier=NTRFR-28440002500011/L=NANTES CEDEX 2/CN=iparapheur.cdg44.fr/serialNumber=0001",
            $user->getDn($certificate)
        );
    }
}
