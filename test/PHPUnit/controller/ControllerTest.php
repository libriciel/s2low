<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Controller\Controller;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SQLQuery;

class ControllerTest extends S2lowIntegrationTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testViewParameter()
    {
        $this->setUpController();
        $this->controller->foo = 42;
        $this->assertTrue($this->controller->isViewParameter('foo'));
    }

    public function testViewParameterFalse()
    {
        $this->setUpController();
        $this->assertFalse($this->controller->isViewParameter('foo'));
    }

    public function testGetViewParameter()
    {
        $this->setUpController();
        $this->controller->foo = 42;
        $this->assertEquals(42, $this->controller->foo);
    }

    public function testGetViewParameterException()
    {
        $this->setUpController();
        $this->expectExceptionMessage('parameter foo not found');
        $this->controller->foo;
    }

    public function testGetAllViewParameter()
    {
        $this->setUpController();
        $this->controller->foo = 42;
        $this->assertEquals(array('foo' => 42), $this->controller->getAllViewParameter());
    }

    public function testRedirect()
    {
        $this->setUpController();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Redirect to http://redirect_url with message : error message");
        $this->controller->redirect("http://redirect_url", "error message");
        $this->assertEquals("error message", $_SESSION['error']);
    }

    public function testVerifAdmin()
    {
        $this->setUpController();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->verifAdmin();
        self::expectNotToPerformAssertions();
    }

    public function testVerifNotConnected()
    {
        $authentication = $this->getAuthentication();
        self::getContainer()->set(Authentification::class, $authentication);


        $this->expectExceptionMessage("Message : Aucune information de certificat trouvée");
        //TODO : l'Exception est thrown lors du setUp du Controller ...
        $this->setUpController();
        $this->controller->verifAdmin();
    }

    public function testVerifNotAdmin()
    {
        $server = [
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_user",
            'SSL_CLIENT_I_DN' => "adullact_user",
            'TESTING_CERTIFICATE_HASH' => "hash_adullact_user",
        ];
        $authentication = $this->getAuthentication($server);
        self::getContainer()->set(Authentification::class, $authentication);

        $this->setUpController();

        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifAdmin();
    }

    public function testRenderDefault()
    {
        $this->setUpController();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->title = "Titre mock";
        $this->controller->template_milieu = __DIR__ . "/../lib/fixtures/MockMockTemplate.php";
        $this->controller->side_bar = false;
        $this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
        $this->controller->renderDefault();
    }

    public function testRender()
    {
        $this->setUpController();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->expectOutputString("<h1>Mock Mock Template</h1>");
        $this->controller->render(__DIR__ . "/../lib/fixtures/MockMockTemplate.php");
    }

    public function testActionBefore()
    {
        $this->setUpController();
        $this->controller->_actionBefore("Mock", "mock");
        $this->assertEquals("S2low", $this->controller->title);
    }

    public function testActionAfter()
    {
        $this->setUpController();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->title = "Titre mock";
        $this->controller->template_milieu = __DIR__ . "/../lib/fixtures/MockMockTemplate.php";
        $this->controller->side_bar = false;
        $this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
        $this->controller->_actionAfter();
    }

    public function testGetRecuperateur()
    {
        $this->setUpController();
        $this->assertInstanceOf(Recuperateur::class, $this->controller->getRecuperateurGet());
    }

    public function testGetRecuperateurPost()
    {
        $this->setUpController();
        $this->assertInstanceOf(Recuperateur::class, $this->controller->getRecuperateurPost());
    }

    public function testGetSqlQuery()
    {
        $this->setUpController();
        $this->assertInstanceOf(SQLQuery::class, $this->controller->getSQLQuery());
    }

    public function testRedirectSSL()
    {
        $this->setUpController();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("/toto/index.php?foo=bar");
        $this->controller->redirectSSL("/toto/index.php", "foo=bar");
    }

    public function testVerifGroupAdmin()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->setUpController();
        $this->controller->verifGroupAdmin(2);
        self::expectNotToPerformAssertions();
    }

    public function testVerifGroupAdminSuperAdmin()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->setUpController();
        $this->controller->verifGroupAdmin(2);
        self::expectNotToPerformAssertions();
    }

    public function testVerifGroupAdminNotAuthorized()
    {
        $this->logAs(3);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->setUpController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Accès refusé");
        $this->controller->verifGroupAdmin(1);
    }

    public function testSetMessage()
    {
        $this->setUpController();
        $this->controller->setMessage("test");
        self::expectNotToPerformAssertions();
    }

    public function testVerifSuperAdmin()
    {
        $this->setUpController();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->verifSuperAdmin();
        self::expectNotToPerformAssertions();
    }

    public function testVerifSuperAdminFailed()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->setUpController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifSuperAdmin();
    }

    public function testVerifAdminAdminGroupOK()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->setUpController();
        $this->controller->verifAdmin(2);
        self::expectNotToPerformAssertions();
    }

    public function testVerifAdminAdminGroupFailed()
    {
        $this->logAs(3);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->setUpController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect to');
        $this->controller->verifAdmin(1);
    }

    public function testVerifAdminOK()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->setUpController();
        $this->controller->verifAdmin(1);
        self::expectNotToPerformAssertions();
    }

    public function testVerifAdminFail()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->setUpController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifAdmin(2);
    }

    public function testGetObjectInstancier()
    {
        $this->setUpController();
        $this->assertInstanceOf(ObjectInstancier::class, $this->controller->getObjectInstancier());
    }

    public function testDisplayErrorAndExitAPI()
    {
        $this->logAs(3);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->post()->set('api', '1');

        $this->setUpController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Exit");
        $this->expectOutputRegex("#Acc\\\u00e8s refus\\\u00e9#");

        $objectInstancierMocked = $this->getMockBuilder(ObjectInstancier::class)
            ->setConstructorArgs([self::getContainer()])
            ->getMock();

        $objectInstancierMocked->method('get')->willReturnCallback(function ($class) use ($environnement) {
            if ($class === Environnement::class) {
                return $environnement;
            }
            return self::getContainer()->get($class);
        });

        $controller = new Controller(
            $objectInstancierMocked,
            self::getContainer()->get(UserContext::class)
        );
        $controller->verifAdmin(1);
    }

    public function testIsApiCall()
    {
        $this->setUpController();
        self::getContainer()->get(Environnement::class)->get()->set('api', '1');
        $this->assertTrue($this->controller->isApiCall());
    }

    /**
     * @return void
     */
    private function setUpController(): void
    {
        $this->controller = self::getContainer()->get(Controller::class);
    }
}
