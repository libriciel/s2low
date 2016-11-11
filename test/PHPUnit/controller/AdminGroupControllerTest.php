<?php

class AdminGroupControllerTest extends S2lowTestCase {

	/** @var  AdminGroupController */
	protected $adminGroupController;

	protected function setUp(){
		parent::setUp();
		$this->adminGroupController = new AdminGroupController($this->getObjectInstancier());
	}

	public function testDoEditAction(){
		$this->setSuperAdminAuthentication();

		org\bovigo\vfs\vfsStream::setup('test');
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$tmp_file = $testStreamUrl."/test.text";

		file_put_contents($tmp_file,"493587273\n");

		$_FILES['siren_file'] = array(
			'name'=>'bar',
			'type'=>'text/plain',
			'size'=>42,
			'tmp_name'=>$tmp_file,
			'error'=>UPLOAD_ERR_OK
		);
		$_POST['id'] = 1;
		$this->setExpectedExceptionRegExp("Exception","#^Redirect to .* with message : $#");
		$this->adminGroupController->doEditAction();

		$authorityGroupSirenSQL = new AuthorityGroupSirenSQL($this->getSQLQuery());
		$this->assertTrue($authorityGroupSirenSQL->exist(1,493587273));
	}

	public function testDoEditActionAdminGroupe(){
		$this->setAdminGroupAuthentication();
		$this->setExpectedExceptionRegExp("Exception","#^Redirect to .* with message : Accès refusé$#");
		$this->adminGroupController->doEditAction();
	}



}