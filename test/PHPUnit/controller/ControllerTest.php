<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Controller\Controller;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SQLQuery;

class ControllerTest extends S2lowTestCase
{
    /**
     * @var Controller
     */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Controller($this->getObjectInstancier());
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
        $this->setExpectedException("Exception", 'parameter foo not found');
        $this->controller->foo;
    }

    public function testGetAllViewParameter()
    {
        $this->controller->foo = 42;
        $this->assertEquals(array('foo' => 42), $this->controller->getAllViewParameter());
    }

    public function testRedirect()
    {
        $this->setExpectedException(RedirectException::class, "Redirect to http://redirect_url with message : error message");
        $this->controller->redirect("http://redirect_url", "error message");
        $this->assertEquals("error message", $_SESSION['error']);
    }

    public function testVerifAdmin()
    {
        $this->setSuperAdminAuthentication();
        $this->controller->verifAdmin();
        $this->noAssertion();
    }

    public function testVerifNotConnected()
    {
        $this->setExpectedException("Exception", "Message : Aucune information de certificat trouvée");
        $this->controller->verifAdmin();
    }

    public function testVerifNotAdmin()
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => "SUCCESS",
            'SSL_CLIENT_S_DN' => "adullact_user",
            'SSL_CLIENT_I_DN' => "adullact_user",
            'TESTING_CERTIFICATE_HASH' => "hash_adullact_user",
        ]);
        $this->setExpectedException("Exception", "Redirect to");
        $this->controller->verifAdmin();
    }

    public function testRenderDefault()
    {
        $this->setSuperAdminAuthentication();
        $this->controller->title = "Titre mock";
        $this->controller->template_milieu = __DIR__ . "/../lib/fixtures/MockMockTemplate.php";
        $this->controller->side_bar = false;
        $this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
        $this->controller->renderDefault();
    }

    public function testRender()
    {
        $this->setSuperAdminAuthentication();
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
        $this->setSuperAdminAuthentication();
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
        $this->setExpectedException(RedirectException::class, "/toto/index.php?foo=bar");
        $this->controller->redirectSSL("/toto/index.php", "foo=bar");
    }

    public function testVerifGroupAdmin()
    {
        $this->setAdminGroupAuthentication();
        $this->controller->verifGroupAdmin(2);
        $this->noAssertion();
    }

    public function testVerifGroupAdminSuperAdmin()
    {
        $this->setSuperAdminAuthentication();
        $this->controller->verifGroupAdmin(2);
        $this->noAssertion();
    }

    public function testVerifGroupAdminNotAuthorized()
    {
        $this->setAdminGroup2Authentication();
        $this->setExpectedException("Exception", "Accès refusé");
        $this->controller->verifGroupAdmin(1);
    }

    public function testSetMessage()
    {
        $this->controller->setMessage("test");
        $this->noAssertion();
    }

    public function testVerifSuperAdmin()
    {
        $this->setSuperAdminAuthentication();
        $this->controller->verifSuperAdmin();
        $this->noAssertion();
    }

    public function testVerifSuperAdminFailed()
    {
        $this->setAdminGroupAuthentication();
        $this->setExpectedException("Exception", "Redirect to");
        $this->controller->verifSuperAdmin();
    }

    public function testVerifAdminAdminGroupOK()
    {
        $this->setAdminGroupAuthentication();
        $this->controller->verifAdmin(2);
        $this->noAssertion();
    }

    public function testVerifAdminAdminGroupFailed()
    {
        $this->setAdminGroup2Authentication();
        $this->setExpectedException("Exception", "Redirect to");
        $this->controller->verifAdmin(1);
    }

    public function testVerifAdminOK()
    {
        $this->setAdminCol2Authentication();
        $this->controller->verifAdmin(2);
        $this->noAssertion();
    }

    public function testVerifAdminFail()
    {
        $this->setAdminCol2Authentication();
        $this->setExpectedException("Exception", "Redirect to");
        $this->controller->verifAdmin(1);
    }

    public function testGetObjectInstancier()
    {
        $this->assertInstanceOf(ObjectInstancier::class, $this->controller->getObjectInstancier());
    }

    public function testDisplayErrorAndExitAPI()
    {
        $this->setAdminGroup2Authentication();
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('api', '1');
        $this->setExpectedException("Exception", "Exit");
        $this->expectOutputRegex("#Acc\\\u00e8s refus\\\u00e9#");
        $this->controller->verifAdmin(1);
    }

    public function testIsApiCall()
    {
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('api', '1');
        $this->assertTrue($this->controller->isApiCall());
    }
}
