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
        $info = $this->userSQL->getInfo(1);
        $this->assertEquals("Eric", $info['givenname']);
    }

    public function testGetInfoNoInfo()
    {
        $this->assertEmpty($this->userSQL->getInfo(-1));
    }

    public function testGetNbUserWithMyCertificate()
    {
        $this->assertEquals(4, $this->userSQL->getNbUserWithMyCertificate('T5k4Cv8eWZMDNWo0h/a6DgDLTVw='));
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
        $this->assertEquals(UserSQL::IDENT_METHOD_CERT_ONLY, $this->userSQL->getIdentificationMethod(1));
    }

    public function testGetIdentificationMethodeNone()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_NONE, $this->userSQL->getIdentificationMethod(-1));
    }

    public function testGetIdentificationMethodeLogin()
    {
        $this->assertEquals(UserSQL::IDENT_METHOD_LOGIN, $this->userSQL->getIdentificationMethod(4));
    }

    public function testGetIdentificationMethodeLibelle()
    {
        $this->assertEquals(
            "Certificat partagé et login/mot de passe",
            $this->userSQL->getIdentificationMethodeLibelle(UserSQL::IDENT_METHOD_LOGIN)
        );
    }

    public function testGetIdFromConnexionInfo()
    {
        $this->assertEquals(
            [["id" => 4, "password" => md5('password')]],
            $this->userSQL->getIdsAndPasswordsFromConnexionInfo(
                'hash_adullact_identification',
                'login'
            )
        );
    }

    public function testGetListIdFromConnexion()
    {
        $this->assertEquals(
            [12, 14],
            $this->userSQL->getIdsFromConnexionInfo('hash_adullact_arch', '')
        );
    }

    public function testFixFingerPrint(): void
    {
        $this->getSQLQuery()->query("UPDATE users SET certificate_hash=?", "");
        $this->userSQL->fixCerticateFingerprint(new X509Certificate());
        $this->assertSame(
            'O6kgdA2cFY5A5ctAmFWRumLZBMY=',
            $this->getSQLQuery()->queryOne("SELECT certificate_hash FROM users WHERE id=?", 3)
        );
    }

    public function testGetListFromCertificateInfo()
    {
        $list = $this->userSQL->getListFromCertificateInfo("hash_user_col1");
        $this->assertEquals(8, $list[0]['id']);
        $this->assertEquals("Bourg-en-Bresse", $list[0]['authority_name']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->userSQL = self::getContainer()->get(UserSQL::class);
    }
}
