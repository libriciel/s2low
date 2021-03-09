<?php

class AdminGroupControllerTest extends S2lowTestCase {

	/** @var  AdminGroupController */
	protected $adminGroupController;

	protected function setUp() : void {
		parent::setUp();
		$this->adminGroupController = new AdminGroupController($this->getObjectInstancier());
	}

	public function testDoEditActionQuote(){
		$this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get("Environnement")->post()->set('id',1);
        $this->getObjectInstancier()->get("Environnement")->post()->set('name', "apo'strophe");

		try {
			$this->adminGroupController->doEditAction();
		} catch (Exception $e){
			/* Nothing to do */
		}

		$groupeSQL = new GroupSQL($this->getSQLQuery());
		$info = $groupeSQL->getInfo(1);
		$this->assertEquals("apo_strophe",$info['name']);
	}

	public function testDoEditAction(){
		$this->setSuperAdminAuthentication();
		org\bovigo\vfs\vfsStream::setup('test');
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$tmp_file = $testStreamUrl."/test.text";

		$authorityGroupSirenSQL = new AuthorityGroupSirenSQL($this->getSQLQuery());

		$this->assertFalse($authorityGroupSirenSQL->exist(1,491011698));

		file_put_contents($tmp_file,"493587273\n491 011 698\n");

		$_FILES['siren_file'] = array(
			'name'=>'bar',
			'type'=>'text/plain',
			'size'=>42,
			'tmp_name'=>$tmp_file,
			'error'=>UPLOAD_ERR_OK
		);

		$this->getObjectInstancier()->get(Environnement::class)->post()->set('id',1);

		try {

			$this->adminGroupController->doEditAction();
			$this->assertFalse(true);
		} catch (Exception $e){
			$this->assertRegExp("#^Redirect to .* with message : $#",$e->getMessage());
		}

		$this->assertEquals('493587273',$authorityGroupSirenSQL->exist(1,493587273)['siren']);
		$this->assertEquals('491011698',$authorityGroupSirenSQL->exist(1,491011698)['siren']);
	}

	public function testDoEditActionAdminGroupe(){
		$this->expectException(Exception::class);
		$this->expectExceptionMessageMatches(
			"#^Message : Aucune information de certificat trouvée$#"
		);

		$this->adminGroupController->doEditAction();
	}

}