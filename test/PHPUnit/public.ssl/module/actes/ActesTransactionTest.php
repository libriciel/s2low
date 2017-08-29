<?php

require_once(__DIR__."/../../../../../public.ssl/modules/actes/class/ActesTransaction.class.php");


class ActesTransactionTest extends S2lowTestCase {

	/** @var  ActesTransaction */
	private $actesTransaction;

	private $pdf_filepath;
	private $xml_filepath;
	private $txt_filepath;

	protected function setUp() {
		parent::setUp();
		$this->actesTransaction = new ActesTransaction();
		$this->actesTransaction->set("destDir","toto");

		$this->pdf_filepath = __DIR__."/../../../fixtures/vide.pdf";
		$this->xml_filepath = __DIR__."/../../../fixtures/toto.xml";
		$this->txt_filepath = __DIR__."/../../../fixtures/toto.txt";
	}

	private function numberTest($number,$valide){
		$this->actesTransaction->set('number',$number);
		$this->actesTransaction->validate();
		$error_msg = $this->actesTransaction->getErrorMsg();
		$number_error = "Le champ Numéro de l'acte ne peut contenir que des chiffres, des lettres en majuscules et _";
		if ($valide){
			$this->assertNotContains($number_error, $error_msg);
		} else {
			$this->assertContains($number_error, $error_msg);
		}
	}

	public function testSetNumber(){
		$this->numberTest("AXY_123",true);
	}

	public function testSetNumberIncorrect(){
		$this->numberTest("foo",false);
	}

	public function testBugNumber(){
		$this->numberTest("_123_AXY",false);
	}

	private function validateAndRemoveFile($filename){
		$actes_destination = ACTES_FILES_UPLOAD_ROOT."/$filename";
		$this->assertTrue(file_exists($actes_destination));
		$this->assertTrue(unlink($actes_destination));
	}

	private function addActePDF(){
		$dest_filename = mt_rand(0,mt_getrandmax());
		$r = $this->actesTransaction->addActeFile("vide.pdf","toto/$dest_filename",$this->pdf_filepath);
		$this->assertTrue($r);
		$this->validateAndRemoveFile("toto/{$dest_filename}.pdf");
	}

	public function addActeXML(){
		$dest_filename = mt_rand(0,mt_getrandmax());
		$this->assertTrue($this->actesTransaction->addActeFile("toto.xml","toto/$dest_filename",$this->xml_filepath));
		$this->validateAndRemoveFile("toto/{$dest_filename}.xml");
	}

	private function addAnnexePDF(){
		$dest_filename2 = mt_rand(0,mt_getrandmax());
		$this->assertTrue($this->actesTransaction->addAttachmentFile("vide.pdf","toto/$dest_filename2",$this->pdf_filepath));
		$this->validateAndRemoveFile("toto/{$dest_filename2}.pdf");
	}

	public function testAddFileActePDF(){
		$this->addActePDF();
	}

	public function testAddAnnexe(){
		$this->addActePDF();
		$this->addAnnexePDF();
		$this->addAnnexePDF();
		$file_list = $this->actesTransaction->fetchFilesList();
		$this->assertEquals(2,count($file_list['attachment']));
	}

	public function testAddActeTxt(){
		$this->assertFalse($this->actesTransaction->addActeFile("toto.txt","toto",$this->txt_filepath));
		$this->assertEquals(
			"Le fichier de l'acte «&nbsp;toto.txt&nbsp;» est de type «&nbsp;inode/x-empty&nbsp;». Fichier PDF ou XML requis.",
			$this->actesTransaction->getErrorMsg()
		);
	}

	private function setActesBudgetaire(){
		$this->actesTransaction->set('nature_code',5);
		$this->actesTransaction->set('classif1',7);
		$this->actesTransaction->set('classif2',1);
	}

	public function testAddActesXML(){
		$this->setActesBudgetaire();
		$this->addActeXML();
	}

	public function testAddActesXMLBadNature(){
		$dest_filename = mt_rand(0,mt_getrandmax());
		$this->assertFalse($this->actesTransaction->addActeFile("toto.xml","toto/$dest_filename",$this->xml_filepath));
		$this->assertEquals("Seul les documents budgétaires et financiers peuvent être au format XML.",$this->actesTransaction->getErrorMsg());
	}

	public function testAddActesXMLBadClassif(){
		$this->actesTransaction->set('nature_code',5);
		$dest_filename = mt_rand(0,mt_getrandmax());
		$this->assertFalse($this->actesTransaction->addActeFile("toto.xml","toto/$dest_filename",$this->xml_filepath));
		$this->assertEquals("Seul la classification 7.1 est autorisé pour la transmission au format XML",$this->actesTransaction->getErrorMsg());
	}

	public function testBadAttachment(){
		$this->assertFalse($this->actesTransaction->addAttachmentFile("toto.txt","toto",$this->txt_filepath));
		$this->assertEquals(
			"Le fichier attaché «&nbsp;toto.txt&nbsp;» est de type «&nbsp;inode/x-empty&nbsp;». Fichier PDF, XML, PNG ou JPEG requis.",
			$this->actesTransaction->getErrorMsg()
		);
	}

	public function testAttachmentXML(){
		$this->setActesBudgetaire();
		$this->addActePDF();
		$dest_filename2 = mt_rand(0,mt_getrandmax());
		$this->assertTrue($this->actesTransaction->addAttachmentFile("vide.xml","toto/".$dest_filename2,$this->xml_filepath));
		$this->validateAndRemoveFile("toto/{$dest_filename2}.xml");
	}

	public function testAttachmentXMLNoBudgetaire(){
		$this->addActePDF();
		$dest_filename2 = mt_rand(0,mt_getrandmax());
		$this->assertFalse($this->actesTransaction->addAttachmentFile("vide.xml","toto/".$dest_filename2,$this->xml_filepath));
		$this->assertEquals("Seul les documents budgétaires et financiers peuvent être au format XML.",$this->actesTransaction->getErrorMsg());
	}

	public function testAddManyXMLAttachment(){
		$this->testAttachmentXML();
		$dest_filename2 = mt_rand(0,mt_getrandmax());
		$this->assertFalse($this->actesTransaction->addAttachmentFile("vide.xml","toto/".$dest_filename2,$this->xml_filepath));
		$this->assertEquals("Un seul attachement XML est autorisé pour les actes budgétaires",$this->actesTransaction->getErrorMsg());
	}

	public function testgenerateActeXMLFile(){
        $this->addActePDF();
        $this->actesTransaction->set('decision_date',"2013-04-05");
        $this->actesTransaction->set('classification_date',"2013-04-05");
        $this->actesTransaction->set('nature_code','1');
        $this->actesTransaction->set('objet','test');
        $this->actesTransaction->set('classif1','1');
        $this->actesTransaction->set('classif2','1');

	    $xml = $this->actesTransaction->generateActeXMLFile("toto");

	    $actesXSD = new \Libriciel\LibActes\ActesXSD();

	    try {
            $actesXSD->validate($xml);
        } catch (\Libriciel\LibActes\Utils\XSDValidationException $e){
            echo $xml;
	        print_r($e->getValidationErrors());
	        throw $e;
        }
    }

    public function testSave(){

        $actesEnvelopeSQL = $this->getObjectInstancier()->get("ActesEnvelopeSQL");

        $envelope_id = $actesEnvelopeSQL->create(1,"000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz");

        $this->actesTransaction->set('envelope_id',$envelope_id);
        $this->actesTransaction->generateActeXMLFile("toto.xml");
        $this->actesTransaction->save();

    }

}