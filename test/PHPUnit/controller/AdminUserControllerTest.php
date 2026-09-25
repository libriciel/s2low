<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Controller\AdminUserController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class AdminUserControllerTest extends S2lowIntegrationTestCase
{
    /**
     * @var AdminUserController
     */
    private $adminUserController;
    private $testStreamUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $_FILES = array();
        $_POST = array();
        org\bovigo\vfs\vfsStream::setup("test");
        $this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");
        $this->adminUserController = self::getContainer()->get(AdminUserController::class);
    }

    private function setDataOk()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->setOnlyDataOk();
    }

    private function setOnlyDataOk()
    {
        $certificate_file = $this->testStreamUrl . "/user1.pem";
        copy(__DIR__ . "/fixtures/user_test.pem", $certificate_file);

        $_FILES['certificate'] = array('name' => 'user_test.pem','tmp_name' => $certificate_file,'size' => filesize($certificate_file));
        $_FILES['certificate_rgs_2_etoiles'] = array('name' => 'user1.pem','tmp_name' => $certificate_file,'size' => filesize($certificate_file));

        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('email', 'eric@sigmalis.com');
        self::getContainer()->get(Environnement::class)->post()->set('name', 'Pommateau');
        self::getContainer()->get(Environnement::class)->post()->set('givenname', 'Eric');
        self::getContainer()->get(Environnement::class)->post()->set('status', UserSQL::STATUS_ACTIVE);
    }

    public function testWithoutCertificatesIn_FILE()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $message = "";
        try {
            $this->adminUserController->doEditAction();
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertDoesNotMatchRegularExpression("/Undefined index: /", $message);
    }

    public function testDoEdit()
    {
        $this->setDataOk();
        $this->adminUserController->doEditAction();
        $this->assertTrue(true);
    }

    public function testDoEditFailed()
    {
        $file = file_get_contents($this->projectDir . '/test/api/Eric_Pommateau_RGS_2_etoiles.pem');
        $pemCertificateFactory = new PemCertificateFactory();
        $certif = $pemCertificateFactory->getFromString(
            $file
        );

        $this->client = $this->createClientWithCertificat(
            $certif->getContent(),
            $certif->getContentStrippedFromBegin(),
            false
        );

        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Message : Aucune information de certificat trouvée');
        $this->adminUserController->doEditAction();
    }

    public function testDoEditApi()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('api', 1);
        $this->expectOutputRegex("#Cr\\\u00e9ation de l'utilisateur Eric Pommateau#");
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->adminUserController->doEditAction();
    }

    public function testDoEditApiFailed()
    {
        $file = file_get_contents($this->projectDir . '/test/api/Eric_Pommateau_RGS_2_etoiles.pem');
        $pemCertificateFactory = new PemCertificateFactory();
        $certif = $pemCertificateFactory->getFromString(
            $file
        );

        $this->client = $this->createClientWithCertificat(
            $certif->getContent(),
            $certif->getContentStrippedFromBegin(),
            false
        );

        self::getContainer()->get(Environnement::class)->post()->set('api', 1);
        $_POST['api'] = 1;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Aucune information de certificat trouvée');
        $this->expectOutputRegex(utf8_decode("#KO\nAucune information de certificat trouvée#"));
        $this->adminUserController->doEditAction();
    }

    public function testDoEditModifNotExistingUser()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('id', 42);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Erreur lors de la modification de l\'utilisateur');
        $this->adminUserController->doEditAction();
    }

    public function testDoEditModifNoRight()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->setOnlyDataOk();
        $this->expectException(Exception::class);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect');

        $this->adminUserController->doEditAction();
    }

    public function testDoEditNoGroupIdForGroupAdmin()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->setAuthorityGroupUserAs(2);
        $this->setOnlyDataOk();
        $this->expectExceptionMessage('La collectivité n\'appartient pas au groupe courant');
        $this->adminUserController->doEditAction();
    }

    public function testDoEditAuthorityIdMandatoryInApi()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('api', 1);
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', '');
        $this->expectExceptionMessage("Exit !");
        $this->expectOutputRegex("#authority_id est obligatoire#");
        $this->adminUserController->doEditAction();
    }

    public function testNotRightToModify()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->setOnlyDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('id', 3);
        $this->expectExceptionMessage("Accès refusé pour la modification de cet utilisateur");
        $this->adminUserController->doEditAction();
    }

    public function testCreateGADMWithoutGroupId()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('role', 'GADM');
        $this->expectExceptionMessage("Vous devez indiquer un groupe pour créer un administrateur de groupe");
        $this->adminUserController->doEditAction();
    }

    public function testPasswordsDontMatch()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('password', 'ku9eiBae');
        self::getContainer()->get(Environnement::class)->post()->set('password', 'Ce6vohya');
        $_POST['password'] = "ku9eiBae";
        $_POST['password2'] = "Ce6vohya";
        $this->expectExceptionMessage(" Les mots de passe ne correspondent pas");
        $this->adminUserController->doEditAction();
    }

    public function testPasswordsDontMatchButCertOnly()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('password', 'ku9eiBae');
        self::getContainer()->get(Environnement::class)->post()->set('password', 'Ce6vohya');
        self::getContainer()->get(Environnement::class)->post()->set('auth_method', UserSQL::IDENT_METHOD_CERT_ONLY);
        $_POST['password'] = "ku9eiBae";
        $_POST['password2'] = "Ce6vohya";
        $this->adminUserController->doEditAction();
        self::expectNotToPerformAssertions();
    }

    public function testNoConnexionCertificate()
    {
        $this->setDataOk();
        $_FILES['certificate']['tmp_name'] = '';
        $this->expectExceptionMessage("Le certificat utilisateur est obligatoire");
        $this->adminUserController->doEditAction();
    }

    public function testCloneWithoutLogin()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('new_id', 1);

        $_POST['new_id'] = 8;
        $this->expectExceptionMessage("Le login et le mot de passe sont obligatoire pour cloner un certificat");
        $this->adminUserController->doEditAction();
    }

    public function testClone()
    {
        $this->setDataOk();
        $_POST['new_id'] = 150;
        $_POST['login'] = 'alice';
        $_POST['password'] = 'eey3fo4A';
        $_POST['password2'] = 'eey3fo4A';
        $this->adminUserController->doEditAction();
        self::expectNotToPerformAssertions();
    }

    public function testCloneSameCertificate()
    {
        $this->setDataOk();
        $this->adminUserController->doEditAction();
        self::getContainer()->get(Environnement::class)->post()->set('new_id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('login', 'alice');
        self::getContainer()->get(Environnement::class)->post()->set('password', 'eey3fo4A');
        self::getContainer()->get(Environnement::class)->post()->set('password2', 'eey3fo4A');


        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier");
        $this->adminUserController->doEditAction();
    }

    public function testModifGroupAdmin()
    {
        $userOfGroup = 5;
        $this->setOnlyDataOk();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        self::getContainer()->get(Environnement::class)->post()->set('login', 'bob');
        self::getContainer()->get(Environnement::class)->post()->set('password', 'eey3fo4A');
        self::getContainer()->get(Environnement::class)->post()->set('password2', 'eey3fo4A');
        self::getContainer()->get(Environnement::class)->post()->set('id', $userOfGroup);
        $this->adminUserController->doEditAction();
        self::expectNotToPerformAssertions();
    }

    public function testGroupAdminCannotModifySuperAdmin(): void
    {
        $superAdmin = 1;
        $this->setOnlyDataOk();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        self::getContainer()->get(Environnement::class)->post()->set('login', 'bob');
        self::getContainer()->get(Environnement::class)->post()->set('password', 'eey3fo4A');
        self::getContainer()->get(Environnement::class)->post()->set('password2', 'eey3fo4A');
        self::getContainer()->get(Environnement::class)->post()->set('id', $superAdmin);

        $this->expectExceptionMessage('Accès refusé pour la modification de cet utilisateur');
        $this->adminUserController->doEditAction();
    }

    public function testCreateDifferentAuthorities()
    {
        $this->setOnlyDataOk();
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 1);
        $this->adminUserController->doEditAction();
        self::expectNotToPerformAssertions();
    }

    public function testSetLogin()
    {
        $this->setDataOk();
        $_POST['login'] = 'alice';
        $_POST['password'] = 'eey3fo4A';
        $_POST['password2'] = 'eey3fo4A';
        $this->adminUserController->doEditAction();
        self::expectExceptionMessage("Un utilisateur avec les mêmes informations de connexion et d'identification existe dans la base S2low");
        $this->adminUserController->doEditAction();
    }

    public function testSetInGroupOK()
    {
        $this->setOnlyDataOk();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 2);
        $this->adminUserController->doEditAction();
        self::expectNotToPerformAssertions();
    }

    public function testForceRole()
    {
        $this->setOnlyDataOk();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 2);
        self::getContainer()->get(Environnement::class)->post()->set('role', 'SADM');
        $user_id = $this->adminUserController->doEditAction();
        $userSQL = self::getContainer()->get(UserSQL::class);
        $user_info = $userSQL->getInfo($user_id);
        $this->assertEquals('ADM', $user_info['role']);
    }

    public function testNotGoodRGSEtoile()
    {
        $this->setDataOk();
        file_put_contents($this->testStreamUrl . "/rogue.pem", "bad certificate");
        $_FILES['certificate_rgs_2_etoiles']['tmp_name'] = $this->testStreamUrl . "/rogue.pem";
        $this->expectExceptionMessage(" Impossible de lire le certificat");
        $this->adminUserController->doEditAction();
    }

    public function testSameInfo()
    {
        $this->setDataOk();
        $this->adminUserController->doEditAction();
        $this->expectExceptionMessage("Un utilisateur avec les mêmes informations de connexion et d'identification existe dans la base S2low");
        $this->adminUserController->doEditAction();
    }

    public function testUserList()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->get()->set('user_id', '2');
        $frontController = self::getContainer()->get(FrontController::class);
        $this->expectOutputRegex("#robert@sigmalis.com#");
        $frontController->go("AdminUser", "list");
    }

    public function testDoBulkModifCertifActionNoUserId()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $frontController = self::getContainer()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Aucun identifiant utilisateur n'a été présenté",
            self::getContainer()->get(Environnement::class)->session()->get('error')
        );
    }

    public function testDoBulkModifCertifActionNoConfirm()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->post()->set('user_id', '2');
        $frontController = self::getContainer()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Vous devez confirmer la modification",
            self::getContainer()->get(Environnement::class)->session()->get('error')
        );
    }

    public function testDoBulkModifCertifActionNoCertif()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->post()->set('user_id', '2');
        self::getContainer()->get(Environnement::class)->post()->set('confirm', 'OUI');

        $frontController = self::getContainer()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Vous devez fournir un certificat",
            self::getContainer()->get(Environnement::class)->session()->get('error')
        );
    }

    public function testDoBulkModifCertifAction()
    {
        $certificate_file = __DIR__ . "/fixtures/test-certificat-not-in-db.pem";
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->post()->set('user_id', '50');
        self::getContainer()->get(Environnement::class)->post()->set('confirm', 'OUI');
        $_FILES['certificat'] = array('tmp_name' => $certificate_file);
        $frontController = self::getContainer()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Certificat mis à jour",
            self::getContainer()->get(Environnement::class)->session()->get('error')
        );

        $userSQL = self::getContainer()->get(UserSQL::class);
        $info = $userSQL->getInfo(51);
        $this->assertEquals(file_get_contents($certificate_file), $info['certificate']);

        $info = $userSQL->getInfo(52);
        $this->assertEquals(file_get_contents($certificate_file), $info['certificate']);
    }

    public function testPasswordIsOk()
    {
        $this->setDataOk();
        self::getContainer()->get(Environnement::class)->post()->set('login', 'login');
        self::getContainer()->get(Environnement::class)->post()->set('password', 'password');
        self::getContainer()->get(Environnement::class)->post()->set('password2', 'password');

        $this->adminUserController->doEditAction();

        $userSQL = self::getContainer()->get(UserSQL::class);

        $certificate_content = file_get_contents(__DIR__ . "/fixtures/user1.pem");

        $x509 = new X509Certificate();
        $certificate_hash = $x509->getBase64Hash(
            $certificate_content,
            UserSQL::CERTIFICATE_FINGERPRINT_HASH_ALG
        );

        $results4 = $userSQL->getIdsAndPasswordsFromConnexionInfo(
            $certificate_hash,
            $certificate_content,
            "login"
        );

        $this->assertTrue(password_verify("password", $results4[0]["password"]));
    }
}
