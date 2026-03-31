<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2low\Security\LegacyAuthenticationBridge;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Controller\AdminGroupController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\GroupSQL;

class AdminGroupControllerTest extends S2lowIntegrationTestCase
{
    /** @var  AdminGroupController */
    protected $adminGroupController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminGroupController = self::getContainer()->get(AdminGroupController::class);
    }

    public function testDoEditActionQuote()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->post()->set('id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('name', "apo'strophe");

        try {
            $this->adminGroupController->doEditAction();
        } catch (Exception $e) {
            /* Nothing to do */
        }

        $groupeSQL = self::getContainer()->get(GroupSQL::class);
        $info = $groupeSQL->getInfo(1);
        $this->assertEquals("apo_strophe", $info['name']);
    }

    public function testDoEditAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        org\bovigo\vfs\vfsStream::setup('test');
        $testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
        $tmp_file = $testStreamUrl . "/test.text";

        $authorityGroupSirenSQL = self::getContainer()->get(AuthorityGroupSirenSQL::class);

        $this->assertFalse($authorityGroupSirenSQL->exist(1, 491011698));

        file_put_contents($tmp_file, "493587273\n491 011 698\n");

        $_FILES['siren_file'] = array(
            'name' => 'bar',
            'type' => 'text/plain',
            'size' => 42,
            'tmp_name' => $tmp_file,
            'error' => UPLOAD_ERR_OK
        );

        self::getContainer()->get(Environnement::class)->post()->set('id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('name', 'Ceci est un nom de groupe');

        try {
            $this->adminGroupController->doEditAction();
            $this->fail();
        } catch (Exception $e) {
            $this->assertMatchesRegularExpression("#^Redirect to .* with message : $#", $e->getMessage());
        }
        $this->assertEquals('493587273', $authorityGroupSirenSQL->exist(1, 493587273)['siren']);
        $this->assertEquals('491011698', $authorityGroupSirenSQL->exist(1, 491011698)['siren']);
    }

    public function testDoEditActionAdminGroupe()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches(
            "#^La connexion n'a pas pu être établie$#"
        );

        // Mock du bridge qui retourne non authentifié
        $authBridge = $this->createMock(LegacyAuthenticationBridge::class);
        $authBridge->method('isAuthenticated')->willReturn(false);

        $auth = $this->getAuthentication($authBridge);

        self::getContainer()->set(Authentification::class, $auth);
        $adminGroup = self::getContainer()->get(AdminGroupController::class);
        $adminGroup->doEditAction();
    }
}
