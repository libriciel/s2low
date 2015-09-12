<?php

require_once(__DIR__."/../init.php");

class ControllerTest extends S2lowTestCase {

	/**
	 * @var Controller
	 */
	private $controller;
	
	protected function setUp(){
		parent::setUp();
		$this->controller = new Controller($this->getObjectInstancier()); 
	}
	
	public function testViewParameter(){
		$this->controller->foo = 42;
		$this->assertTrue($this->controller->isViewParameter('foo'));
	}
	
	public function testViewParameterFalse(){
		$this->assertFalse($this->controller->isViewParameter('foo'));
	}
	
	public function testGetViewParameter(){
		$this->controller->foo = 42;
		$this->assertEquals(42,$this->controller->foo);
	}
	
	public function testGetViewParameterException(){
		$this->setExpectedException("Exception",'parameter foo not found');
		$this->controller->foo;
	}
	
	public function testGetAllViewParameter(){
		$this->controller->foo = 42;
		$this->assertEquals(array('foo'=>42),$this->controller->getViewParameter());
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testRedirect(){
		$this->setExpectedException("RedirectException","Redirect to http://redirect_url with message : error message");
		$this->controller->redirect("http://redirect_url","error message");
		$this->assertEquals("error message",$_SESSION['error']);
	}
	
	public function testVerifAdmin(){
		$this->setSuperAdminAuthentication();
		$this->controller->verifAdmin();
	}
	
	public function testVerifNotConnected(){
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->controller->verifAdmin();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testVerifNotAdmin(){
		$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$_SERVER['SSL_CLIENT_S_DN'] = "adullact_user";
		$_SERVER['SSL_CLIENT_I_DN'] = "adullact_user";
		$this->setExpectedException("Exception","Accès refusé");
		$this->controller->verifAdmin();
	}
	
	public function testRenderDefault(){
		$this->controller->title = "Titre mock";
		$this->controller->template_milieu = __DIR__."/../lib/fixtures/MockMockTemplate.php";
		$this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
		$this->controller->renderDefault();
	}
	
	public function testRender(){
		$this->expectOutputString("<h1>Mock Mock Template</h1>");
		$this->controller->render(__DIR__."/../lib/fixtures/MockMockTemplate.php");
	}
	
	public function testActionBefore(){
		$this->controller->_actionBefore("Mock", "mock");
		$this->assertEquals("S2low",$this->controller->title);
	}
	
	public function testActionAfter(){
		$this->controller->title = "Titre mock";
		$this->controller->template_milieu = __DIR__."/../lib/fixtures/MockMockTemplate.php";
		$this->expectOutputRegex("#<h1>Mock Mock Template</h1>#");
		$this->controller->_actionAfter();
	}
	
	public function testGetRecuperateur(){
		$this->assertInstanceOf("Recuperateur",$this->controller->getRecuperateurGet());
	}
	
	public function testGetRecuperateurPost(){
		$this->assertInstanceOf("Recuperateur",$this->controller->getRecuperateurPost());
	}
	
	public function testGetSqlQuery(){
		$this->assertInstanceOf("SQLQuery",$this->controller->getSQLQuery());
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testRedirectSSL(){
		$this->setExpectedException("RedirectException","/toto/index.php?foo=bar");
		$this->controller->redirectSSL("/toto/index.php","foo=bar");
	}
	
	public function testVerifGroupAdmin(){
		$this->setAdminGroupAuthentication();
		$this->controller->verifGroupAdmin(2);
	}
	
	public function testVerifGroupAdminSuperAdmin(){
		$this->setSuperAdminAuthentication();
		$this->controller->verifGroupAdmin(2);
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testVerifGroupAdminNotAuthorized(){
		$this->setAdminGroupAuthentication();
		$this->setExpectedException("Exception","Accès refusé");
		$this->controller->verifGroupAdmin(1);
	}
	
	public function testSetMessage(){
		$this->controller->setMessage("test");
	}
	
	public function testVerifSuperAdmin(){
		$this->setSuperAdminAuthentication();
		$this->controller->verifSuperAdmin();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testVerifSuperAdminFailed(){
		$this->setAdminGroupAuthentication();
		$this->setExpectedException("Exception","Accès refusé");
		$this->controller->verifSuperAdmin();
	}
	
	public function testVerifAdminAdminGroupOK(){
		$this->setAdminGroupAuthentication();
		$this->controller->verifAdmin(2);
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testVerifAdminAdminGroupFailed(){
		$this->setAdminGroupAuthentication();
		$this->setExpectedException("Exception","Accès refusé");
		$this->controller->verifAdmin(1);
	}
	
	public function testVerifAdminOK(){
		$this->setAdminCol2Authentication();
		$this->controller->verifAdmin(2);
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testVerifAdminFail(){
		$this->setAdminCol2Authentication();
		$this->setExpectedException("Exception","Accès refusé");
		$this->controller->verifAdmin(1);
	}
	
	public function testGetObjectInstancier(){
		$this->assertInstanceOf("ObjectInstancier",$this->controller->getObjectInstancier());
	}
	

	
	
}