<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\actes;

use ActesEnvelope;
use ActesTransaction;
use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;

class ActesTransactionTest extends S2lowTestCase
{
    /** @var  ActesTransaction */
    private $actesTransaction;

    private $pdf_filepath;
    private $xml_filepath;
    private $txt_filepath;
    private $jpg_filepath;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransaction = new ActesTransaction();
        $this->actesTransaction->set("destDir", "toto");

        $this->pdf_filepath = __DIR__ . "/../../../fixtures/vide.pdf";
        $this->xml_filepath = __DIR__ . "/../../../fixtures/toto.xml";
        $this->txt_filepath = __DIR__ . "/../../../fixtures/toto.txt";
        $this->jpg_filepath = __DIR__ . "/../../../fixtures/test.jpg";
    }

    private function numberTest($number, $valide)
    {
        $this->actesTransaction->set('number', $number);
        $this->actesTransaction->validate();
        $error_msg = $this->actesTransaction->getErrorMsg();
        $number_error = "Le champ Numéro de l'acte ne peut contenir que des chiffres, des lettres en majuscules et _";
        if ($valide) {
            $this->assertStringNotContainsString($number_error, $error_msg);
        } else {
            $this->assertStringContainsString($number_error, $error_msg);
        }
    }

    public function testSetNumber()
    {
        $this->numberTest("AXY_123", true);
    }

    public function testSetNumberIncorrect()
    {
        $this->numberTest("foo", false);
    }

    public function testBugNumber()
    {
        $this->numberTest("_123_AXY", false);
    }

    private function validateAndRemoveFile($filename)
    {
        $actes_destination = ACTES_FILES_UPLOAD_ROOT . "/$filename";
        $this->assertTrue(file_exists($actes_destination));
        $this->assertTrue(unlink($actes_destination));
    }

    private function addActePDF()
    {
        $dest_filename = mt_rand(0, mt_getrandmax());
        $r = $this->actesTransaction->addActeFile("vide.pdf", "toto/$dest_filename", $this->pdf_filepath);
        $this->assertTrue($r);
        $this->validateAndRemoveFile("toto/{$dest_filename}.pdf");
    }

    private function addActeJPG()
    {
        $dest_filename = mt_rand(0, mt_getrandmax());
        $r = $this->actesTransaction->addActeFile("test.jpg", "toto/$dest_filename", $this->jpg_filepath);
        $this->assertTrue($r);
        $this->validateAndRemoveFile("toto/{$dest_filename}.jpg");
    }

    public function addActeXML()
    {
        $dest_filename = mt_rand(0, mt_getrandmax());
        $this->assertTrue($this->actesTransaction->addActeFile("toto.xml", "toto/$dest_filename", $this->xml_filepath));
        $this->validateAndRemoveFile("toto/{$dest_filename}.xml");
    }

    private function addAnnexePDF()
    {
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        $this->assertTrue($this->actesTransaction->addAttachmentFile("vide.pdf", "toto/$dest_filename2", $this->pdf_filepath));
        $this->validateAndRemoveFile("toto/{$dest_filename2}.pdf");
    }

    public function testAddFileActePDF()
    {
        $this->addActePDF();
    }

    public function testAddAnnexe()
    {
        $this->addActePDF();
        $this->addAnnexePDF();
        $this->addAnnexePDF();
        $file_list = $this->actesTransaction->fetchFilesList();
        $this->assertEquals(2, count($file_list['attachment']));
    }

    public function testAddJPGCourrierSimple()
    {
        $this->actesTransaction->set('type', 3);
        $this->addActeJPG();
    }

    public function testAddTextCourrierSimple()
    {
        $this->actesTransaction->set('type', 3);
        $this->assertFalse($this->actesTransaction->addActeFile("toto.txt", "toto", $this->txt_filepath));
        $this->assertEquals(
            "Le fichier de réponse \" toto.txt \" est de type \" application/x-empty \". Fichier PDF, XML, PNG ou JPEG requis.",
            $this->actesTransaction->getErrorMsg()
        );
    }

    public function testAddActeTxt()
    {
        $this->actesTransaction->set('type', 1);
        $this->assertFalse($this->actesTransaction->addActeFile("toto.txt", "toto", $this->txt_filepath));
        $this->assertEquals(
            "Le fichier de l'acte \" toto.txt \" est de type \" application/x-empty \". Fichier PDF ou XML requis.",
            $this->actesTransaction->getErrorMsg()
        );
    }

    private function setActesBudgetaire()
    {
        $this->actesTransaction->set('nature_code', 5);
        $this->actesTransaction->set('classif1', 7);
        $this->actesTransaction->set('classif2', 1);
    }

    public function testAddActesXML()
    {
        $this->setActesBudgetaire();
        $this->addActeXML();
    }

    public function testAddActesXMLBadNature()
    {
        $this->actesTransaction->set('type', 1);
        $dest_filename = mt_rand(0, mt_getrandmax());
        $this->assertFalse($this->actesTransaction->addActeFile("toto.xml", "toto/$dest_filename", $this->xml_filepath));
        $this->assertEquals("Seuls les documents budgétaires et financiers peuvent être au format XML.", $this->actesTransaction->getErrorMsg());
    }

    public function testAddActesXMLBadClassif()
    {
        $this->actesTransaction->set('type', 1);
        $this->actesTransaction->set('nature_code', 5);
        $dest_filename = mt_rand(0, mt_getrandmax());
        $this->assertFalse($this->actesTransaction->addActeFile("toto.xml", "toto/$dest_filename", $this->xml_filepath));
        $this->assertEquals("Seule la classification 7.1 est autorisée pour la transmission au format XML", $this->actesTransaction->getErrorMsg());
    }

    public function testBadAttachment()
    {
        $this->assertFalse($this->actesTransaction->addAttachmentFile("toto.txt", "toto", $this->txt_filepath));
        $this->assertEquals(
            "Le fichier attaché \" toto.txt \" est de type \" application/x-empty \". Fichier PDF, XML, PNG ou JPEG requis.",
            $this->actesTransaction->getErrorMsg()
        );
    }

    public function testAttachmentXML()
    {
        $this->setActesBudgetaire();
        $this->addActePDF();
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        $this->assertTrue($this->actesTransaction->addAttachmentFile("vide.xml", "toto/" . $dest_filename2, $this->xml_filepath));
        $this->validateAndRemoveFile("toto/{$dest_filename2}.xml");
    }

    public function testAttachmentXMLNoBudgetaire()
    {
        $this->addActePDF();
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        $this->assertTrue($this->actesTransaction->addAttachmentFile("vide.xml", "toto/" . $dest_filename2, $this->xml_filepath));
    }

    public function testAddManyXMLAttachment()
    {
        $this->testAttachmentXML();
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        $this->assertTrue($this->actesTransaction->addAttachmentFile("vide.xml", "toto/" . $dest_filename2, $this->xml_filepath));
    }

    /**
     * @throws Exception
     * @throws \Libriciel\LibActes\Utils\XSDValidationException
     */
    public function testgenerateActeXMLFile()
    {
        $this->addActePDF();
        $this->actesTransaction->set('decision_date', "2013-04-05");
        $this->actesTransaction->set('classification_date', "2013-04-05");
        $this->actesTransaction->set('nature_code', '1');
        $this->actesTransaction->set('objet', 'test');
        $this->actesTransaction->set('classif1', '1');
        $this->actesTransaction->set('classif2', '1');

        $xml = $this->actesTransaction->generateActeXMLFile("toto");

        $actesXSD = new \Libriciel\LibActes\ActesXSD();

        try {
            $actesXSD->validate($xml);
        } catch (\Libriciel\LibActes\Utils\XSDValidationException $e) {
            echo $xml;
            print_r($e->getValidationErrors());
            throw $e;
        }
    }

    public function testSave()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $envelope_id = $actesEnvelopeSQL->create(1, "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz");

        $this->actesTransaction->set('envelope_id', $envelope_id);
        $this->actesTransaction->set('decision_date', '2017-08-29');
        $this->actesTransaction->set('classification_date', '2017-08-29');
        $this->actesTransaction->set('classif1', '1');
        $this->actesTransaction->set('classif2', '1');

        $this->actesTransaction->set('type', '1');
        $this->actesTransaction->set('nature_code', '1');
        $this->actesTransaction->set('nature_descr', 'toto');
        $this->actesTransaction->set('subject', 'TEST');
        $this->actesTransaction->set('number', 'TEST');


        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $dest_name = $this->actesTransaction->getStdFileName($env);

        $this->actesTransaction->addActeFile("vide.pdf", $dest_name, $this->pdf_filepath);

        $dest_name = $this->actesTransaction->getStdFileName($env, true, "99_AU");
        $this->actesTransaction->addAttachmentFile(
            "vide2.pdf",
            "$dest_name",
            $this->pdf_filepath,
            true,
            '99_AU'
        );
        $xml_name =  $this->actesTransaction->getStdFileName($env, false);

        $this->actesTransaction->generateMessageXMLFile($xml_name);

        $this->actesTransaction->save();

        $transaction_id = $this->actesTransaction->getId();

        $actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);
        $file_list = $actesIncludedFileSQL->getAll($transaction_id);
        $this->assertEquals("99_AU", $file_list[2]['code_pj']);
        $this->assertEquals("99_AU-001-000000000-20170829-TEST-DE-1-1_2.pdf", $file_list[2]['filename']);
    }

    public function testGetTransactionNatureDescr()
    {
        $this->assertEquals(['short_descr' => 'DE','descr' => 'Deliberations'], ActesTransaction::getTransactionNatureDescr(1));
    }
    public function testGetTransactionNatureDescrFailed()
    {
        $this->assertFalse(ActesTransaction::getTransactionNatureDescr('Délibération'));
    }


    public function testSaveWithIncorectTypologie()
    {
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $envelope_id = $actesEnvelopeSQL->create(1, "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz");

        $this->actesTransaction->set('envelope_id', $envelope_id);
        $this->actesTransaction->set('decision_date', '2017-08-29');
        $this->actesTransaction->set('classification_date', '2017-08-29');
        $this->actesTransaction->set('classif1', '1');
        $this->actesTransaction->set('classif2', '1');

        $this->actesTransaction->set('type', '1');
        $this->actesTransaction->set('nature_code', '1');
        $this->actesTransaction->set('nature_descr', 'toto');
        $this->actesTransaction->set('subject', 'TEST');
        $this->actesTransaction->set('number', 'TEST');


        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $dest_name = $this->actesTransaction->getStdFileName($env);

        $this->actesTransaction->addActeFile("vide.pdf", $dest_name, $this->pdf_filepath);

        $dest_name = $this->actesTransaction->getStdFileName($env, true, "code_pj_trop_grand");
        $result = $this->actesTransaction->addAttachmentFile(
            "vide2.pdf",
            "$dest_name",
            $this->pdf_filepath,
            true,
            'code_pj_trop_grand'
        );
        $this->assertFalse($result);
        $this->assertEquals(
            'Le code de la PJ doit faire 5 caractères',
            $this->actesTransaction->getErrorMsg()
        );
    }
}
