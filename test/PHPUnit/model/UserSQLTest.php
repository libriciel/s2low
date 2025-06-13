<?php

use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class UserSQLTest extends S2lowTestCase
{
    /**
     * @var UserSQL
     */
    private $userSQL;

    public function testGetInfo()
    {
        $info = $this->userSQL->getInfo(101);
        $this->assertEquals("Eric", $info['givenname']);
    }

    public function testGetInfoNoInfo()
    {
        $this->assertEmpty($this->userSQL->getInfo(-1));
    }

    public function testGetNbUserWithMyCertificate()
    {
        $this->assertEquals(1, $this->userSQL->getNbUserWithMyCertificate('Q1pUbEb5DK53BkYf0arDl/3zl5U='));
    }

    public function testGetInfoFromCertificateInfo()
    {
        $certificateInfo = array('certificate_hash' => 'admin_col1');
        $info = $this->userSQL->getInfoFromCertificateInfo($certificateInfo);
        $this->assertEquals("Alice", $info[0]['givenname']);
    }

    public function testGetRoleStr()
    {
        $this->assertEquals("Super administrateur", $this->userSQL->getRoleStr("SADM"));
    }

    public function testGetRoleException()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Rôle Rick inconnu");
        $this->userSQL->getRoleStr("Rick");
    }

    public function testGetIdentificationMethode()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_CERT_ONLY, $this->userSQL->getIdentificationMethod(101));
    }

    public function testGetIdentificationMethodeNone()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_NONE, $this->userSQL->getIdentificationMethod(-1));
    }

    public function testGetIdentificationMethodeRGS()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_RGS_2_ETOILES, $this->userSQL->getIdentificationMethod(103));
    }

    public function testGetIdentificationMethodeLogin()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_LOGIN, $this->userSQL->getIdentificationMethod(104));
    }

    public function testGetIdentificationMethodeLibelle()
    {
        $this->assertEquals(
            "Certificat partagé et login/mot de passe",
            $this->userSQL->getIdentificationMethodeLibelle(UserSQL::IDENT_METHOD_LOGIN)
        );
    }

    public function testSaveCertificateRGS2Etoile()
    {
        $this->userSQL->saveCertificateRGS2Etoiles(113, "pem_content");
        $info = $this->userSQL->getInfo(113);
        $this->assertEquals("pem_content", $info['certificate_rgs_2_etoiles']);
        $this->assertEquals(1, $info['nb_user_with_my_certificate']);
    }

    public function testDeleteCertificateRGS2Etoile()
    {
        $this->userSQL->deleteCertificateRGS2Etoiles(104);
        $info = $this->userSQL->getInfo(104);
        $this->assertEmpty($info['certificate_rgs_2_etoiles']);
    }

    public function testUpdateCertificateIfNull()
    {
        $sql = "UPDATE users SET certificate_rgs_2_etoiles=NULL WHERE id=?";
        $this->getSQLQuery()->query($sql, 103);
        $this->userSQL->updateCertificatRGS2EtoilesIfNull(103);
        $info = $this->userSQL->getInfo(103);
        $this->assertEmpty($info['certificate_rgs_2_etoiles']);
    }

    public function testGetIdFromConnexionInfo()
    {
        $this->assertEquals(
            [["id" => 104, "password" => md5('password')]],
            $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
                'hash_adullact_identification',
                '',
                'login'
            )
        );
    }

    public function testGetListIdFromConnexion()
    {
        $this->assertEquals(
            [112, 114],
            $this->userSQL->getListIdFromConnexion('hash_adullact_arch', '')
        );
    }

    public function testFixFingerPrint()
    {
        $this->getSQLQuery()->query("UPDATE users SET certificate_hash=?", "");
        $this->userSQL->fixCerticateFingerprint(new X509Certificate());
        $this->assertEquals(
            "O6kgdA2cFY5A5ctAmFWRumLZBMY=",
            $this->getSQLQuery()->queryOne("SELECT certificate_hash FROM users WHERE id=?", 103)
        );
    }

    public function testGetListFromCertificateInfo()
    {
        $list = $this->userSQL->getListFromCertificateInfo("hash_user_col1");
        $this->assertEquals(108, $list[0]['id']);
        $this->assertEquals("Bourg-en-Bresse", $list[0]['authority_name']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSQL = self::getContainer()->get(UserSQL::class);
    }
}
