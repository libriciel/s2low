<?php

use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class UserSQLTest extends S2lowTestCase
{
    /**
     * @var UserSQL
     */
    private $userSQL;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSQL = new UserSQL($this->getSQLQuery());
    }

    public function testGetInfo()
    {
        $info = $this->userSQL->getInfo(1);
        $this->assertEquals("Eric", $info['givenname']);
    }

    public function testGetInfoNoInfo()
    {
        $this->assertEmpty($this->userSQL->getInfo(-1));
    }

    public function testGetNbUserWithMyCertificate()
    {
        $this->assertEquals(2, $this->userSQL->getNbUserWithMyCertificate('hash_adullact'));
    }

    public function testGetInfoFromCertificateInfo()
    {
        $certificateInfo = array('certificate_hash' => 'hash_adullact');
        $info = $this->userSQL->getInfoFromCertificateInfo($certificateInfo);
        $this->assertEquals("Alice", $info[0]['givenname']);
    }

    public function testGetRoleStr()
    {
        $this->assertEquals("Super administrateur", $this->userSQL->getRoleStr("SADM"));
    }

    public function testGetIdentificationMethode()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_CERT_ONLY, $this->userSQL->getIdentificationMethod(1));
    }

    public function testGetIdentificationMethodeNone()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_NONE, $this->userSQL->getIdentificationMethod(-1));
    }

    public function testGetIdentificationMethodeRGS()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_RGS_2_ETOILES, $this->userSQL->getIdentificationMethod(4));
    }

    public function testGetIdentificationMethodeLogin()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_LOGIN, $this->userSQL->getIdentificationMethod(2));
    }

    public function testGetIdentificationMethodeLibelle()
    {
        $this->assertEquals("Certificat partagé et login/mot de passe", $this->userSQL->getIdentificationMethodeLibelle(UserSQL::IDENT_METHOD_LOGIN));
    }

    public function testSaveCertificateRGS2Etoile()
    {
        $this->userSQL->saveCertificateRGS2Etoiles(1, "pem_content");
        $info = $this->userSQL->getInfo(1);
        $this->assertEquals("pem_content", $info['certificate_rgs_2_etoiles']);
        $this->assertEquals(1, $info['nb_user_with_my_certificate']);
    }

    public function testDeleteCertificateRGS2Etoile()
    {
        $this->userSQL->deleteCertificateRGS2Etoiles(4);
        $info = $this->userSQL->getInfo(4);
        $this->assertEmpty($info['certificate_rgs_2_etoiles']);
    }

    public function testUpdateCertificateIfNull()
    {
        $sql = "UPDATE users SET certificate_rgs_2_etoiles=NULL WHERE id=?";
        $this->getSQLQuery()->query($sql, 1);
        $this->userSQL->updateCertificatRGS2EtoilesIfNull(1);
        $info = $this->userSQL->getInfo(1);
        $this->assertEmpty($info['certificate_rgs_2_etoiles']);
    }

    public function testGetIdFromConnexionInfo()
    {
        $this->assertEquals(
            [["id" => 2,"password" => md5('alice')]],
            $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
                'hash_adullact',
                '',
                'alice'
            )
        );
    }

    public function testGetListIdFromConnexion()
    {
        $this->assertEquals(array(2, 3), $this->userSQL->getListIdFromConnexion('hash_adullact', ''));
    }

    public function testFixFingerPrint()
    {
        $this->getSQLQuery()->query("UPDATE users SET certificate_hash=?", "");
        $this->userSQL->fixCerticateFingerprint(new X509Certificate());
        $this->assertEquals("ieQoLUcitdU9iZIJLPoIdp8TcUY=", $this->getSQLQuery()->queryOne("SELECT certificate_hash FROM users WHERE id=?", 1));
    }

    public function testGetListFromCertificateInfo()
    {
        $list = $this->userSQL->getListFromCertificateInfo("hash_adullact");
        $this->assertEquals(2, $list[0]['id']);
        $this->assertEquals("Bourg-en-Bresse", $list[1]['authority_name']);
    }
}
