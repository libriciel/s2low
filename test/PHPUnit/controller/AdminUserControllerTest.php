<?php

use S2lowLegacy\Controller\AdminUserController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

class AdminUserControllerTest extends S2lowTestCase
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
        $this->adminUserController = new AdminUserController($this->getObjectInstancier());
    }

    private function setDataOk()
    {
        $this->setSuperAdminAuthentication();
        $this->setOnlyDataOk();
    }

    private function setOnlyDataOk()
    {
        $certificate_file = $this->testStreamUrl . "/user1.pem";
        copy(__DIR__ . "/fixtures/user1.pem", $certificate_file);

        $_FILES['certificate'] = array('name' => 'user1.pem','tmp_name' => $certificate_file,'size' => filesize($certificate_file));
        $_FILES['certificate_rgs_2_etoiles'] = array('name' => 'user1.pem','tmp_name' => $certificate_file,'size' => filesize($certificate_file));

        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 1);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('email', 'eric@sigmalis.com');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('name', 'Pommateau');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('givenname', 'Eric');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('status', UserSQL::STATUS_ACTIVE);
    }

    public function testWithoutCertificatesIn_FILE()
    {
        $this->setSuperAdminAuthentication();
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
        $this->setExpectedException("Exception", "Message : Aucune information de certificat trouvée");
        $this->adminUserController->doEditAction();
    }

    public function testDoEditApi()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('api', 1);
        $this->expectOutputRegex("#Cr\\\u00e9ation de l'utilisateur Eric Pommateau#");
        $this->setExpectedException("Exception", "exit() called");
        $this->adminUserController->doEditAction();
    }

    public function testDoEditApiFailed()
    {
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('api', 1);
        $_POST['api'] = 1;
        $this->setExpectedException("Exception", "Aucune information de certificat trouvée");
        $this->expectOutputRegex("#KO\nAucune information de certificat trouvée#");
        $this->adminUserController->doEditAction();
    }

    public function testDoEditModifNotExistingUser()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('id', 42);
        $this->setExpectedException("Exception", "Erreur lors de la modification de l'utilisateur");
        $this->adminUserController->doEditAction();
    }

    public function testDoEditModifNoRight()
    {
        $this->setUserAuthentification();
        $this->setOnlyDataOk();
        $this->setExpectedException(Exception::class, "Redirect");
        $this->adminUserController->doEditAction();
    }

    public function testDoEditNoGroupIdForGroupAdmin()
    {
        $this->setAdminGroup2Authentication();
        $this->setOnlyDataOk();
        $this->setExpectedException("Exception", "La collectivité n'appartient pas au groupe courant");
        $this->adminUserController->doEditAction();
    }

    public function testDoEditAuthorityIdMandatoryInApi()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('api', 1);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', '');
        $this->setExpectedException("Exception", "Exit !");
        $this->expectOutputRegex("#authority_id est obligatoire#");
        $this->adminUserController->doEditAction();
    }

    public function testNotRightToModify()
    {
        $this->setAdminCol2Authentication();
        $this->setOnlyDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('id', 1);
        $this->setExpectedException("Exception", "Accès refusé pour la modification de cet utilisateur");
        $this->adminUserController->doEditAction();
    }

    public function testCreateGADMWithoutGroupId()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('role', 'GADM');
        $this->setExpectedException("Exception", "Vous devez indiquer un groupe pour créer un administrateur de groupe");
        $this->adminUserController->doEditAction();
    }

    public function testPasswordsDontMatch()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'ku9eiBae');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'Ce6vohya');
        $_POST['password'] = "ku9eiBae";
        $_POST['password2'] = "Ce6vohya";
        $this->setExpectedException("Exception", " Les mots de passe ne correspondent pas");
        $this->adminUserController->doEditAction();
    }

    public function testPasswordsDontMatchButCertOnly()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'ku9eiBae');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'Ce6vohya');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('auth_method', UserSQL::IDENT_METHOD_CERT_ONLY);
        $_POST['password'] = "ku9eiBae";
        $_POST['password2'] = "Ce6vohya";
        $this->adminUserController->doEditAction();
        $this->noAssertion();
    }

    public function testNoConnexionCertificate()
    {
        $this->setDataOk();
        $_FILES['certificate']['tmp_name'] = '';
        $this->setExpectedException("Exception", "Le certificat utilisateur est obligatoire");
        $this->adminUserController->doEditAction();
    }

    public function testCloneWithoutLogin()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('new_id', 8);

        $_POST['new_id'] = 8;
        $this->setExpectedException("Exception", "Le login et le mot de passe sont obligatoire pour cloner un certificat");
        $this->adminUserController->doEditAction();
    }

    public function testClone()
    {
        $this->setDataOk();
        $_POST['new_id'] = 8;
        $_POST['login'] = 'alice';
        $_POST['password'] = 'eey3fo4A';
        $_POST['password2'] = 'eey3fo4A';
        $this->adminUserController->doEditAction();
        $this->noAssertion();
    }

    public function testCloneSameCertificate()
    {
        $this->setDataOk();
        $this->adminUserController->doEditAction();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('new_id', 8);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('login', 'alice');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'eey3fo4A');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password2', 'eey3fo4A');


        $this->setExpectedException("Exception", "Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier");
        $this->adminUserController->doEditAction();
    }

    public function testModifGroupAdmin()
    {
        $this->setOnlyDataOk();
        $this->setAdminGroupAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('login', 'bob');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'eey3fo4A');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password2', 'eey3fo4A');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('id', 6);
        $this->adminUserController->doEditAction();
        $this->noAssertion();
    }

    public function testCreateDifferentAuthorities()
    {
        $this->setOnlyDataOk();
        $this->setAdminCol2Authentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 1);
        $this->adminUserController->doEditAction();
        $this->noAssertion();
    }

    public function testSetLogin()
    {
        $this->setDataOk();
        $_POST['login'] = 'alice';
        $_POST['password'] = 'eey3fo4A';
        $_POST['password2'] = 'eey3fo4A';
        $this->adminUserController->doEditAction();
        $this->setExpectedException("Exception", "Un utilisateur avec les mêmes informations de connexion et d'identification existe dans la base S2low");
        $this->adminUserController->doEditAction();
    }

    public function testSetInGroupOK()
    {
        $this->setOnlyDataOk();
        $this->setAdminGroupAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 2);
        $this->adminUserController->doEditAction();
        $this->noAssertion();
    }

    public function testForceRole()
    {
        $this->setOnlyDataOk();
        $this->setAdminGroupAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 2);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('role', 'SADM');
        $user_id = $this->adminUserController->doEditAction();
        $userSQL = new UserSQL($this->getSQLQuery());
        $user_info = $userSQL->getInfo($user_id);
        $this->assertEquals('ADM', $user_info['role']);
    }

    public function testNotGoodRGSEtoile()
    {
        $this->setDataOk();
        file_put_contents($this->testStreamUrl . "/rogue.pem", "bad certificate");
        $_FILES['certificate_rgs_2_etoiles']['tmp_name'] = $this->testStreamUrl . "/rogue.pem";
        $this->setExpectedException("Exception", " Impossible de lire le certificat");
        $this->adminUserController->doEditAction();
    }

    public function testSameInfo()
    {
        $this->setDataOk();
        $this->adminUserController->doEditAction();
        $this->setExpectedException("Exception", "Un utilisateur avec les mêmes informations de connexion et d'identification existe dans la base S2low");
        $this->adminUserController->doEditAction();
    }

    public function testUserList()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('user_id', '2');
        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $this->expectOutputRegex("#eric\+3@sigmalis.com#");
        $frontController->go("AdminUser", "list");
    }

    public function testDoBulkModifCertifActionNoUserId()
    {
        $this->setSuperAdminAuthentication();
        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Aucun identifiant utilisateur n'a été présenté",
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );
    }

    public function testDoBulkModifCertifActionNoConfirm()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('user_id', '2');
        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Vous devez confirmer la modification",
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );
    }

    public function testDoBulkModifCertifActionNoCertif()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('user_id', '2');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('confirm', 'OUI');

        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Vous devez fournir un certificat",
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );
    }

    public function testDoBulkModifCertifAction()
    {
        $certificate_file = __DIR__ . "/fixtures/user1.pem";
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('user_id', '2');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('confirm', 'OUI');
        $_FILES['certificat'] = array('tmp_name' => $certificate_file);

        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $frontController->go("AdminUser", "doBulkModifCertif");
        $this->assertEquals(
            "Certificat mis à jour",
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );

        $userSQL = $this->getObjectInstancier()->get(UserSQL::class);
        $info = $userSQL->getInfo(2);
        $this->assertEquals($info['certificate'], file_get_contents($certificate_file));

        $info = $userSQL->getInfo(3);
        $this->assertEquals($info['certificate'], file_get_contents($certificate_file));
    }

    public function testPasswordIsOk()
    {
        $this->setDataOk();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('login', 'login');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password', 'password');
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('password2', 'password');

        $this->adminUserController->doEditAction();

        $userSQL = $this->getObjectInstancier()->get(UserSQL::class);

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
