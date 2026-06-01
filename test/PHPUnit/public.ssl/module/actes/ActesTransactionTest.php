<?php

declare(strict_types=1);

use S2lowLegacy\Class\actes\ActesClassificationCodesSQL;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\TypeTransaction;

class ActesTransactionTest extends S2lowTestCase
{
    /** @var  ActesTransaction */
    private ActesTransaction $actesTransaction;

    private string $pdf_filepath;
    private string $xml_filepath;
    private string $txt_filepath;
    private string $jpg_filepath;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransaction = new ActesTransaction();
        $this->actesTransaction->set('destDir', 'toto');

        $this->pdf_filepath = __DIR__ . '/../../../fixtures/vide.pdf';
        $this->xml_filepath = __DIR__ . '/../../../fixtures/toto.xml';
        $this->txt_filepath = __DIR__ . '/../../../fixtures/toto.txt';
        $this->jpg_filepath = __DIR__ . '/../../../fixtures/test.jpg';
    }

    private function numberTest($number, $valide)
    {
        $this->actesTransaction->set('number', $number);
        $this->actesTransaction->validate();
        $error_msg = $this->actesTransaction->getErrorMsg();
        $number_error = "Le champ Numéro de l'acte ne peut contenir que des chiffres, des lettres en majuscules et _";
        if ($valide) {
            static::assertStringNotContainsString($number_error, $error_msg);
        } else {
            static::assertStringContainsString($number_error, $error_msg);
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
        static::assertTrue(file_exists($actes_destination));
        static::assertTrue(unlink($actes_destination));
    }

    private function addActePDF()
    {
        $dest_filename = mt_rand(0, mt_getrandmax());
        $r = $this->actesTransaction->addActeFile("vide.pdf", "toto/$dest_filename", $this->pdf_filepath);
        static::assertTrue($r);
        $this->validateAndRemoveFile("toto/{$dest_filename}.pdf");
    }

    private function addActeJPG()
    {
        $dest_filename = mt_rand(0, mt_getrandmax());
        $r = $this->actesTransaction->addActeFile("test.jpg", "toto/$dest_filename", $this->jpg_filepath);
        static::assertTrue($r);
        $this->validateAndRemoveFile("toto/{$dest_filename}.jpg");
    }

    public function addActeXML()
    {
        $dest_filename = mt_rand(0, mt_getrandmax());
        static::assertTrue($this->actesTransaction->addActeFile("toto.xml", "toto/$dest_filename", $this->xml_filepath));
        $this->validateAndRemoveFile("toto/{$dest_filename}.xml");
    }

    private function addAnnexePDF()
    {
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        static::assertTrue($this->actesTransaction->addAttachmentFile("vide.pdf", "toto/$dest_filename2", $this->pdf_filepath));
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
        static::assertSame(2, count($file_list['attachment']));
    }

    public function testAddJPGCourrierSimple()
    {
        $this->actesTransaction->setType(TypeTransaction::DemandePieceComplementaire);
        $this->addActeJPG();
    }

    public function testAddTextCourrierSimple()
    {
        $this->actesTransaction->setType(TypeTransaction::DemandePieceComplementaire);
        self::assertFalse($this->actesTransaction->addActeFile("toto.txt", "toto", $this->txt_filepath));
        self::assertSame(
            "Le fichier de réponse \" toto.txt \" est de type \" application/x-empty \". Fichier PDF, XML, PNG ou JPEG requis.",
            $this->actesTransaction->getErrorMsg()
        );
    }

    public function testAddActeTxt()
    {
        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
        self::AssertFalse($this->actesTransaction->addActeFile("toto.txt", "toto", $this->txt_filepath));
        self::assertSame(
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
        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
        $dest_filename = mt_rand(0, mt_getrandmax());
        static::assertFalse($this->actesTransaction->addActeFile("toto.xml", "toto/$dest_filename", $this->xml_filepath));
        static::assertSame("Seuls les documents budgétaires et financiers peuvent être au format XML.", $this->actesTransaction->getErrorMsg());
    }

    public function testAddActesXMLBadClassif()
    {
        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
        $this->actesTransaction->set('nature_code', 5);
        $dest_filename = mt_rand(0, mt_getrandmax());
        static::assertFalse($this->actesTransaction->addActeFile("toto.xml", "toto/$dest_filename", $this->xml_filepath));
        static::assertSame("Seule la classification 7.1 est autorisée pour la transmission au format XML", $this->actesTransaction->getErrorMsg());
    }

    public function testBadAttachment()
    {
        self::assertFalse($this->actesTransaction->addAttachmentFile("toto.txt", "toto", $this->txt_filepath));
        self::assertSame(
            "Le fichier attaché \" toto.txt \" est de type \" application/x-empty \". Fichier PDF, XML, PNG ou JPEG requis.",
            $this->actesTransaction->getErrorMsg()
        );
    }

    public function testAttachmentXML()
    {
        $this->setActesBudgetaire();
        $this->addActePDF();
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        static::assertTrue($this->actesTransaction->addAttachmentFile("vide.xml", "toto/" . $dest_filename2, $this->xml_filepath));
        $this->validateAndRemoveFile("toto/{$dest_filename2}.xml");
    }

    public function testAttachmentXMLNoBudgetaire()
    {
        $this->addActePDF();
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        static::assertTrue($this->actesTransaction->addAttachmentFile("vide.xml", "toto/" . $dest_filename2, $this->xml_filepath));
    }

    public function testAddManyXMLAttachment()
    {
        $this->testAttachmentXML();
        $dest_filename2 = mt_rand(0, mt_getrandmax());
        static::assertTrue($this->actesTransaction->addAttachmentFile("vide.xml", "toto/" . $dest_filename2, $this->xml_filepath));
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

        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
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
        self::assertSame(
            $xml_name . '_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        static::assertSame('99_AU', $file_list[2]['code_pj']);
        static::assertSame('99_AU-001-000000000-20170829-TEST-DE-1-1_2.pdf', $file_list[2]['filename']);
    }

    public function testGetTransactionNatureDescr()
    {
        self::assertSame(['short_descr' => 'DE','descr' => 'Deliberations'], ActesTransaction::getTransactionNatureDescr(1));
    }
    public function testGetTransactionNatureDescrFailed()
    {
        static::assertFalse(ActesTransaction::getTransactionNatureDescr('Délibération'));
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

        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
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
        self::assertFalse($result);
        self::assertSame(
            'Le code de la PJ doit faire 5 caractères',
            $this->actesTransaction->getErrorMsg()
        );
    }

    /**
     * @dataProvider typeProvider
     */
    public function testFileNameAccordingToType(TypeTransaction $type, string $expectedName): void
    {
        $this->actesTransaction->setType($type);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');
        static::assertSame($expectedName, $this->actesTransaction->getStdFileName($env));
    }

    public function typeProvider(): iterable
    {
        return [
            [TypeTransaction::TransmissionActe, 'toto/001-000000000-20130405--DE-1-1_1'],
            [TypeTransaction::CourrierSimple, 'toto/001-000000000-20130405--DE-2-2_1'],
            [TypeTransaction::DemandeDeClassification, 'toto/001-000000000----7-1_1']
        ];
    }

    public function testFileNameAnnulation(): void
    {
        $this->actesTransaction->setType(TypeTransaction::Annulation);

        $relatedTransaction = new ActesTransaction();
        $relatedTransaction->set('decision_date', '2013-04-05');
        $relatedTransaction->set('nature_code', '1');

        $this->actesTransaction->set('related_transaction', $relatedTransaction);

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $this->actesTransaction->setType(TypeTransaction::Annulation);
        static::assertSame('toto/001-000000000-20130405--DE-6-1_1', $this->actesTransaction->getStdFileName($env));
    }

    /**
     * Les lettres d'observation et les demandes complémentaires utilisent un type de réponse
     * Il faut donc un test séparé.
     * @dataProvider typeProviderWithResponseType
     */
    public function testFileNameAccordingToTypeWithResponseType(TypeTransaction $type, int $type_response, string $expectedName)
    {

        $this->actesTransaction->setType($type);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');
        $this->actesTransaction->set('type_reponse', $type_response);

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');
        self::assertSame($expectedName, $this->actesTransaction->getStdFileName($env));
    }

    public function typeProviderWithResponseType()
    {
        return [
            [TypeTransaction::LettreDObservation,1, 'toto/001-000000000-20130405--DE-4-1_1'],
            [TypeTransaction::LettreDObservation,2, 'toto/001-000000000-20130405--DE-4-2_1'],
            [TypeTransaction::DemandePieceComplementaire,1, 'toto/001-000000000-20130405--DE-3-1_1'],
            [TypeTransaction::DemandePieceComplementaire,2, 'toto/001-000000000-20130405--DE-3-2_1']
        ];
    }

    public function testGenerateMessageXMLActe()
    {

        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('classification_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');

        $this->addActePDF();

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        static::assertTrue($this->actesTransaction->generateMessageXMLFile('test'));
        $file_path = ACTES_FILES_UPLOAD_ROOT . '/' . $this->actesTransaction->get('xmlFileName');
        static::assertSame(
            'test_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        static::assertFileExists($file_path);

        $rootname_for_a_TransmissionActe_Message = '<actes:Acte';
        static::assertStringContainsString($rootname_for_a_TransmissionActe_Message, file_get_contents($file_path));
    }

    /**
     * @dataProvider typeReponseCourrier
     */
    public function testGenerateMessageXMLReponseCourrier(TypeTransaction $type, ?int $type_response, string $expectedRoot): void
    {
        $this->actesTransaction->setType($type);
        $this->actesTransaction->set('type_reponse', $type_response);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('classification_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');

        $this->addActePDF();

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $relatedTransaction = new ActesTransaction();
        $relatedTransaction->set('decision_date', '2013-04-05');
        $relatedTransaction->set('nature_code', '1');

        $this->actesTransaction->set('related_transaction', $relatedTransaction);


        static::assertTrue($this->actesTransaction->generateMessageXMLFile('test'));
        $file_path = ACTES_FILES_UPLOAD_ROOT . '/' . $this->actesTransaction->get('xmlFileName');
        static::assertSame(
            'test_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        static::assertFileExists($file_path);
        // On vérifie que le type de message généré est correct
        static::assertStringContainsString('<actes:' . $expectedRoot, file_get_contents($file_path));
    }

    public function typeReponseCourrier(): iterable
    {
        return [
            [TypeTransaction::CourrierSimple, null, 'ReponseCourrierSimple'],
            [TypeTransaction::DemandePieceComplementaire, ActesTransaction::TYPE_REFUS, 'RefusPieceComplementaire'],
            [TypeTransaction::LettreDObservation, ActesTransaction::TYPE_REFUS, 'RejetLettreObservations'],
            [TypeTransaction::LettreDObservation, ActesTransaction::TYPE_ENVOIE, 'ReponseLettreObservations'],
        ];
    }

    public function testGenerateMessageXMLAnnulation(): void
    {

        $this->actesTransaction->setType(TypeTransaction::Annulation);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('classification_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $relatedTransaction = new ActesTransaction();
        $relatedTransaction->set('decision_date', '2013-04-05');
        $relatedTransaction->set('nature_code', '1');
        $relatedTransaction->set('unique_id', 'ACTE_A_ANNULER');

        $this->actesTransaction->set('related_transaction', $relatedTransaction);

        static::assertTrue($this->actesTransaction->generateMessageXMLFile('test'));
        $file_path = ACTES_FILES_UPLOAD_ROOT . '/' . $this->actesTransaction->get('xmlFileName');
        static::assertSame(
            'test_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        static::assertFileExists($file_path);
        $file_content = file_get_contents($file_path);
        // On vérifie que le type de message généré est correct
        static::assertStringContainsString('<actes:Annulation', $file_content);
        // On vérifie que l'unique id de l'acte à annuler est correct
        static::assertStringContainsString('actes:IDActe="ACTE_A_ANNULER"', $file_content);
    }

    public function testGenerateMessageXMLDemandeClassification(): void
    {

        $this->actesTransaction->setType(TypeTransaction::DemandeDeClassification);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('last_classification_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        static::assertTrue($this->actesTransaction->generateMessageXMLFile('test'));
        $file_path = ACTES_FILES_UPLOAD_ROOT . '/' . $this->actesTransaction->get('xmlFileName');
        static::assertSame(
            'test_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        static::assertFileExists($file_path);
        $file_content = file_get_contents($file_path);
        // On vérifie que le type de message généré est correct
        static::assertStringContainsString('<actes:DemandeClassification', $file_content);
        // On vérifie que la date de dernière classification est présente et correcte
        static::assertStringContainsString(
            '<actes:DateClassification>2013-04-05</actes:DateClassification>',
            $file_content
        );
    }
    public function testGenerateMessageXMLReponseCourrierEnvoi(): void
    {
        $this->actesTransaction->setType(TypeTransaction::DemandePieceComplementaire);
        $this->actesTransaction->set('type_reponse', ActesTransaction::TYPE_ENVOIE);
        $this->actesTransaction->set('decision_date', '2013-04-05');
        $this->actesTransaction->set('classification_date', '2013-04-05');
        $this->actesTransaction->set('nature_code', '1');

        $this->addActePDF();
        $this->addAnnexePDF();

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $relatedTransaction = new ActesTransaction();
        $relatedTransaction->set('decision_date', '2013-04-05');
        $relatedTransaction->set('nature_code', '1');

        $this->actesTransaction->set('related_transaction', $relatedTransaction);

        static::assertTrue($this->actesTransaction->generateMessageXMLFile('test'));
        $file_path = ACTES_FILES_UPLOAD_ROOT . '/' . $this->actesTransaction->get('xmlFileName');
        static::assertSame(
            'test_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        static::assertFileExists($file_path);
        $file_content = file_get_contents($file_path);
        // On vérifie que le type de message généré est correct
        static::assertStringContainsString('<actes:PieceComplementaire', $file_content);
        static::assertStringContainsString('<actes:Documents>', $file_content);
        // On vérifie que le document principal est présent et correct
        static::assertStringContainsString(
            basename($this->actesTransaction->files['acte']['name']),
            $file_content
        );
        // On vérifie que le document annexe est présent et correct
        static::assertStringContainsString(
            basename($this->actesTransaction->files['attachment'][0]['name']),
            $file_content
        );
        unlink('/data/tdt-workspace/actes/uploads/test_0.xml');
    }

    /**
     * @dataProvider XMLfiles
     */
    public function testCreateFromXML(string $filename, int $type): void
    {
        $test_file_path = __DIR__ . "/../../../../../vendor/libriciel/tdt-lib-actes/tests/FichierXML/fixtures/$filename";
        copy($test_file_path, ACTES_FILES_UPLOAD_ROOT . '/' . $filename);

        $transaction = new ActesTransaction();
        $transaction->createFromXML(
            $filename,
            $this->getObjectInstancier()->get(ActesClassificationCodesSQL::class)
        );

        unlink(ACTES_FILES_UPLOAD_ROOT . '/' . $filename);

        self::assertSame(
            $type,
            $transaction->get('type')
        );
    }

    public function XMLfiles(): iterable
    {
        return [
            //actes:Acte
          ['001-000000000-20170130-TEST42-DE-1-1_0.xml', TypeTransaction::TransmissionActe->value],
            //actes:ReponseCourrierSimple
            ['001-000000000-20170130-TEST42-DE-2-2_0.xml', TypeTransaction::CourrierSimple->value],
            //actes:RefusPieceComplementaire
            ['001-000000000-20170130-TEST42-DE-3-3_0.xml',TypeTransaction::DemandePieceComplementaire->value],
            //actes:PieceComplementaire
            ['001-000000000-20170130-TEST42-DE-3-4_0.xml',TypeTransaction::DemandePieceComplementaire->value],
            //actes:RejetLettreObservations
            ['001-000000000-20170130-TEST42-DE-4-3_0.xml',TypeTransaction::LettreDObservation->value],
            //actes:ReponseLettreObservations
            ['001-000000000-20170130-TEST42-DE-4-4_0.xml',TypeTransaction::LettreDObservation->value],
            //actes:Annulation
            ['001-000000000-20170130-TEST42-DE-6-1_0.xml',TypeTransaction::Annulation->value],
            //actes:DemandeClassification
            ['001-000000000----7-1_0.xml',TypeTransaction::DemandeDeClassification->value],
        ];
    }

    public function testsetDataFromCourrierWithExistingRelatedTransaction(): void
    {
        $filename = __DIR__ . '/fixtures/001-000000000-20170130-TEST42-DE-3-3_0.xml';

        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $envelope_id = $actesEnvelopeSQL->create(1, '000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz');

        $this->actesTransaction->set('envelope_id', $envelope_id);
        $this->actesTransaction->set('decision_date', '2017-08-29');
        $this->actesTransaction->set('classification_date', '2017-08-29');
        $this->actesTransaction->set('classif1', '1');
        $this->actesTransaction->set('classif2', '1');

        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
        $this->actesTransaction->set('nature_code', '1');
        $this->actesTransaction->set('nature_descr', 'toto');
        $this->actesTransaction->set('subject', 'TEST');
        $this->actesTransaction->set('number', 'TEST');

        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $dest_name = $this->actesTransaction->getStdFileName($env);

        $this->actesTransaction->addActeFile('vide.pdf', $dest_name, $this->pdf_filepath);
        $this->actesTransaction->getStdFileName($env, true, "99_AU");

        $xml_name =  $this->actesTransaction->getStdFileName($env, false);

        $this->actesTransaction->generateMessageXMLFile($xml_name);

        $this->actesTransaction->set('unique_id', '032-213201601-20170616-ARP201706407-AI');
        $this->actesTransaction->save();

        mkdir(ACTES_FILES_UPLOAD_ROOT . '/testFiles/');
        copy($filename, ACTES_FILES_UPLOAD_ROOT . '/testFiles/' . basename($filename));
        copy(__DIR__ . '/fixtures/test_pdf.pdf', ACTES_FILES_UPLOAD_ROOT . '/testFiles/' . '001-000000000-20170130-TEST42-DE-3-3_1.pdf');


        $transaction = new ActesTransaction();
        $transaction->set('destDir', 'testFiles');
        $transaction->createFromXML(
            '/testFiles/' . basename($filename),
            $this->getObjectInstancier()->get(ActesClassificationCodesSQL::class)
        );


        unlink(ACTES_FILES_UPLOAD_ROOT . '/testFiles/' . basename($filename));
        unlink(ACTES_FILES_UPLOAD_ROOT . '/testFiles/' . '001-000000000-20170130-TEST42-DE-3-3_1.pdf');
        rmdir(ACTES_FILES_UPLOAD_ROOT . '/testFiles/');

        static::assertSame(
            $xml_name . '_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );
        self::assertSame(
            TypeTransaction::DemandePieceComplementaire->value,
            $transaction->get('type')
        );
        self::assertSame(
            $xml_name . '_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );

        self::assertSame(null, $transaction->getErrorMsg());
        self::assertSame(
            '/testFiles/001-000000000-20170130-TEST42-DE-3-3_1.pdf',
            $transaction->get('files')['acte']['name']
        );
    }

    public function testsetDataFromCourrierWithExistingRelatedTransaction2(): void
    {
        $filename = __DIR__ . '/fixtures/001-000000000-20170130-TEST42-DE-3-4_0.xml';
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $envelope_id = $actesEnvelopeSQL->create(1, '000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz');

        $this->actesTransaction->set('envelope_id', $envelope_id);
        $this->actesTransaction->set('decision_date', '2017-08-29');
        $this->actesTransaction->set('classification_date', '2017-08-29');
        $this->actesTransaction->set('classif1', '1');
        $this->actesTransaction->set('classif2', '1');

        $this->actesTransaction->setType(TypeTransaction::TransmissionActe);
        $this->actesTransaction->set('nature_code', '1');
        $this->actesTransaction->set('nature_descr', 'toto');
        $this->actesTransaction->set('subject', 'TEST');
        $this->actesTransaction->set('number', 'TEST');


        $env = new ActesEnvelope();
        $env->set('department', '001');
        $env->set('siren', '000000000');

        $dest_name = $this->actesTransaction->getStdFileName($env);

        $this->actesTransaction->addActeFile('vide.pdf', $dest_name, $this->pdf_filepath);

        $this->actesTransaction->getStdFileName($env, true, '99_AU');
        $xml_name =  $this->actesTransaction->getStdFileName($env, false);

        $this->actesTransaction->generateMessageXMLFile($xml_name);

        $this->actesTransaction->set('unique_id', '032-213201601-20170616-ARP201706407-AI');
        $this->actesTransaction->save();

        if (!is_dir(ACTES_FILES_UPLOAD_ROOT . '/siren')) {
            mkdir(ACTES_FILES_UPLOAD_ROOT . '/siren');
        }
        if (!is_dir(ACTES_FILES_UPLOAD_ROOT . '/siren/import/')) {
            mkdir(ACTES_FILES_UPLOAD_ROOT . '/siren/import/');
        }
        copy($filename, ACTES_FILES_UPLOAD_ROOT . '/siren/import/' . basename($filename));
        copy(__DIR__ . '/fixtures/test_pdf.pdf', ACTES_FILES_UPLOAD_ROOT . '/siren/import/001-000000000-20170130-TEST42-DE-3-4_1.pdf');
        copy(__DIR__ . '/fixtures/test_pdf.pdf', ACTES_FILES_UPLOAD_ROOT .  '/siren/import/001-000000000-20170130-TEST42-DE-3-4_2.pdf');


        $transaction = new ActesTransaction();
        $transaction->set('destDir', '/siren/import/');
        $transaction->createFromXML(
            '/siren/import/' . basename($filename),
            $this->getObjectInstancier()->get(ActesClassificationCodesSQL::class)
        );


        unlink(ACTES_FILES_UPLOAD_ROOT . '/siren/import/' . basename($filename));
        unlink(ACTES_FILES_UPLOAD_ROOT . '/siren/import/001-000000000-20170130-TEST42-DE-3-4_1.pdf');
        unlink(ACTES_FILES_UPLOAD_ROOT . '/siren/import/001-000000000-20170130-TEST42-DE-3-4_2.pdf');
        rmdir(ACTES_FILES_UPLOAD_ROOT . '/siren/import');
        rmdir(ACTES_FILES_UPLOAD_ROOT . '/siren');

        self::assertSame(
            TypeTransaction::DemandePieceComplementaire->value,
            $transaction->get('type')
        );
        self::assertSame(
            $xml_name . '_0.xml',
            $this->actesTransaction->get('xmlFileName')
        );

        //TODO : il y a un bug
        // 001-000000000-20170130-TEST42-DE-3-4_2.pdf devrait être dans les annexes
        // et 001-000000000-20170130-TEST42-DE-3-4_1.pdf devrait figurer en fichier principal
        self::assertSame(
            '/siren/import/001-000000000-20170130-TEST42-DE-3-4_2.pdf',
            $transaction->get('files')['acte']['name']
        );
    }

    public function testGenerateMessageXMLFileMauvaisType(): void
    {
        $this->actesTransaction->set('type', 666);
        self::assertFalse($this->actesTransaction->generateMessageXMLFile('test'));
        self::assertSame(
            'Mauvais type de transaction.',
            $this->actesTransaction->getErrorMsg()
        );
    }

    /**
     * @dataProvider typeProvider2
     */
    public function testIsType(int $type_as_int, bool $isSame)
    {
        $this->actesTransaction->set('type', $type_as_int);
        self::assertSame(
            $isSame,
            $this->actesTransaction->isType(TypeTransaction::TransmissionActe)
        );
    }

    public function typeProvider2(): iterable
    {
        return [
            [29620,false],
            [TypeTransaction::TransmissionActe->value, true],
            [TypeTransaction::DemandeDeClassification->value, false],
        ];
    }
}
