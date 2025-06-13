<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\Authentification;
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
        $this->controller = self::getContainer()->get(Controller::class);
    }

    public function testViewParameter()
    {
        $this->controller->foo = 42;
        $this->assertTrue($this->controller->isViewParameter('foo'));
    }

    public function testViewParameterFalse()
    {
        $this->assertFalse($this->controller->isViewParameter('foo'));
    }

    public function testGetViewParameter()
    {
        $this->controller->foo = 42;
        $this->assertEquals(42, $this->controller->foo);
    }

    public function testGetViewParameterException()
    {
        $this->expectExceptionMessage('parameter foo not found');
        $this->controller->foo;
    }

    public function testGetAllViewParameter()
    {
        $this->controller->foo = 42;
        $this->assertEquals(array('foo' => 42), $this->controller->getAllViewParameter());
    }

    public function testRedirect()
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Redirect to http://redirect_url with message : error message");
        $this->controller->redirect("http://redirect_url", "error message");
        $this->assertEquals("error message", $_SESSION['error']);
    }

    public function testVerifAdmin()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->verifAdmin();
        self::expectNotToPerformAssertions();
    }

    public function testVerifNotConnected()
    {
        $authentication = $this->getAuthentication();
        self::getContainer()->set(Authentification::class, $authentication);

        $this->expectExceptionMessage("Message : Aucune information de certificat trouvée");
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

        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifAdmin();
    }

    public function testRenderDefault()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->title = "Titre mock";
        $this->controller->template_milieu = __DIR__ . "/../lib/fixtures/MockMockTemplate.php";
        $this->controller->side_bar = false;
        $this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
        $this->controller->renderDefault();
    }

    public function testRender()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->expectOutputString("<h1>Mock Mock Template</h1>");
        $this->controller->render(__DIR__ . "/../lib/fixtures/MockMockTemplate.php");
    }

    public function testActionBefore()
    {
        $this->controller->_actionBefore("Mock", "mock");
        $this->assertEquals("S2low", $this->controller->title);
    }

    public function testActionAfter()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->title = "Titre mock";
        $this->controller->template_milieu = __DIR__ . "/../lib/fixtures/MockMockTemplate.php";
        $this->controller->side_bar = false;
        $this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
        $this->controller->_actionAfter();
    }

    public function testGetRecuperateur()
    {
        $this->assertInstanceOf(Recuperateur::class, $this->controller->getRecuperateurGet());
    }

    public function testGetRecuperateurPost()
    {
        $this->assertInstanceOf(Recuperateur::class, $this->controller->getRecuperateurPost());
    }

    public function testGetSqlQuery()
    {
        $this->assertInstanceOf(SQLQuery::class, $this->controller->getSQLQuery());
    }

    public function testRedirectSSL()
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("/toto/index.php?foo=bar");
        $this->controller->redirectSSL("/toto/index.php", "foo=bar");
    }

    public function testVerifGroupAdmin()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->verifGroupAdmin(2);
        self::expectNotToPerformAssertions();
    }

    public function testVerifGroupAdminSuperAdmin()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->verifGroupAdmin(2);
        self::expectNotToPerformAssertions();
    }

    public function testVerifGroupAdminNotAuthorized()
    {
        $this->logAs(103);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Accès refusé");
        $this->controller->verifGroupAdmin(101);
    }

    public function testSetMessage()
    {
        $this->controller->setMessage("test");
        self::expectNotToPerformAssertions();
    }

    public function testVerifSuperAdmin()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->controller->verifSuperAdmin();
        self::expectNotToPerformAssertions();
    }

    public function testVerifSuperAdminFailed()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifSuperAdmin();
    }

    public function testVerifAdminAdminGroupOK()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->controller->verifAdmin(102);
        self::expectNotToPerformAssertions();
    }

    public function testVerifAdminAdminGroupFailed()
    {
        $this->logAs(103);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifAdmin(101);
    }

    public function testVerifAdminOK()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->controller->verifAdmin(101);
        self::expectNotToPerformAssertions();
    }

    public function testVerifAdminFail()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Redirect to");
        $this->controller->verifAdmin(102);
    }

    public function testGetObjectInstancier()
    {
        $this->assertInstanceOf(ObjectInstancier::class, $this->controller->getObjectInstancier());
    }

    public function testDisplayErrorAndExitAPI()
    {
        $this->logAs(103);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->post()->set('api', '1');
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
        );
        $controller->verifAdmin(101);
    }

    public function testIsApiCall()
    {
        self::getContainer()->get(Environnement::class)->get()->set('api', '1');
        $this->assertTrue($this->controller->isApiCall());
    }
}
