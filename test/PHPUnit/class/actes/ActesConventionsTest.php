<?php

class ActesConventionsTest extends S2lowTestCase {

    private $actes_files_upload_root;

    /** @var  ActesConventions */
    private $actesConventions;

    protected function setUp() : void {
        parent::setUp();
        $tmpFolder = new TmpFolder();
        $this->actes_files_upload_root = $tmpFolder->create();
        $this->getObjectInstancier()->set(
            'actes_files_upload_root',
            $this->actes_files_upload_root
        );

        $this->actesConventions = $this->getObjectInstancier()->get("ActesConventions");
    }

    protected function tearDown() : void {
        parent::tearDown();
        $tmpFolder = new TmpFolder();
        $tmpFolder->delete($this->actes_files_upload_root);
    }

    public function testHasNoConvention(){
        $this->assertFalse($this->actesConventions->hasConvention(1));
    }

    public function testHasConvention(){
        $this->actesConventions->setConvention(1,__DIR__."/fixtures/convention-exemple.pdf");
        $this->assertTrue($this->actesConventions->hasConvention(1));
    }

    public function testGetConventionFilename(){
        $this->actesConventions->setConvention(1,__DIR__."/fixtures/convention-exemple.pdf");
        $this->assertEquals(
            "123456789-convention-actes.pdf",
            $this->actesConventions->getConventionFilename(1)
        );
    }

    public function testGetConventionFilepath(){
        $this->actesConventions->setConvention(1,__DIR__."/fixtures/convention-exemple.pdf");
        $this->assertEquals(
            $this->actes_files_upload_root."/123456789/123456789-convention-actes.pdf",
            $this->actesConventions->getConventionFilepath(1)
        );
    }

    public function testHasNoConventionNoArgs(){
        $this->assertFalse($this->actesConventions->hasConvention(0));
    }

    public function testGetConventionFilenameNoException(){
        $this->setExpectedException("Exception","Aucune convention présente pour la collectivité 0");
        $this->actesConventions->getConventionFilename(0);
    }

    public function testGetConventionFilenameNoException2(){
        $this->setExpectedException("Exception","Aucune convention présente pour la collectivité 18");
        $this->actesConventions->getConventionFilename(18);
    }

}