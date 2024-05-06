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

    public function testSaveUser()
    {
        $user = new User();
        $user->set("email", "em@i.l");
        $user->set("certFilePath", __DIR__ . '/fixtures/certificats/dateOk/fullchain.pem');
        $user->set("name", "name");
        $user->set("givenname", "givenName");
        $user->set("role", "USER");
        $user->set("telephone", "0000000000");
        $user->set("authority_id", 1);
        $user->set("status", 1);
        $this->assertTrue($user->save());

        $userId = $user->getId();

        $retrievedUser = new User($userId);
        $retrievedUser->init();

        $this->assertEquals($retrievedUser->get("email"), "em@i.l");
        $this->assertEquals($retrievedUser->get("name"), "name");
        $this->assertEquals($retrievedUser->get("givenname"), "givenName");
        $this->assertEquals($retrievedUser->get("role"), "USER");
        $this->assertEquals($retrievedUser->get("telephone"), "0000000000");
        $this->assertEquals($retrievedUser->get("authority_id"), 1);
        $this->assertEquals($retrievedUser->get("status"), 1);
    }

    /**
     * @dataProvider archivistRights
     */
    public function testSaveUserArchivistRight(bool $archivistRight)
    {
        $user = new User();
        $user->set("email", "em@i.l");
        $user->set("certFilePath", __DIR__ . '/fixtures/certificats/dateOk/fullchain.pem');
        $user->set("name", "name");
        $user->set("givenname", "givenName");
        $user->set("role", "USER");
        $user->set("telephone", "0000000000");
        $user->set("authority_id", 1);
        $user->set("status", 1);
        $user->set("archivist_rights", $archivistRight);
        $user->save();

        $retrievedUser = new User($user->getId());
        $retrievedUser->init();
        $this->assertEquals($archivistRight, $retrievedUser->hasArchivistsRights());
    }

    public function archivistRights(): iterable
    {
        yield [true];
        yield [false];
    }

    public function testResetArchivistRight()
    {
        $user = new User();
        $user->set("email", "em@i.l");
        $user->set("certFilePath", __DIR__ . '/fixtures/certificats/dateOk/fullchain.pem');
        $user->set("name", "name");
        $user->set("givenname", "givenName");
        $user->set("role", "USER");
        $user->set("telephone", "0000000000");
        $user->set("authority_id", 1);
        $user->set("status", 1);
        $user->set("archivist_right", true);
        $user->save();

        $user->set("archivist_right", false);
        $user->save();

        $retrievedUser = new User($user->getId());
        $retrievedUser->init();
        $this->assertEquals($retrievedUser->hasArchivistsRights(), false);
    }
}
