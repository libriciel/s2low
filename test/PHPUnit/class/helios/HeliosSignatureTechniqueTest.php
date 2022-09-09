<?php

use S2lowLegacy\Class\helios\HeliosPESValidation;
use S2lowLegacy\Class\helios\HeliosSignatureTechnique;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\helios\UnrecoverableHeliosSignatureTechniqueException;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Controller\HeliosController;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\PKCS12;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Lib\XadesSignature;
use S2lowLegacy\Lib\XadesSignatureParser;
use S2lowLegacy\Lib\XadesSignatureProperties;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosSignatureTechniqueTest extends S2lowTestCase
{
    private $transaction_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->transaction_id = $this->importFile(__DIR__ . "/../../helios/fixtures/pes_aller_ok.xml");
    }

    private function importFile($pes_aller)
    {
        /** @var PesAllerRetriever $pesAllerRetriever */
        $pesAllerRetriever = $this->getObjectInstancier()->get(PesAllerRetriever::class);
        $filepath = $pesAllerRetriever->getPathForNonExistingFile(sha1_file($pes_aller));

        copy($pes_aller, $filepath);
        $heliosControler = new HeliosController($this->getObjectInstancier());

        return $heliosControler->importFile(8, $pes_aller, "pes_aller.xml");
    }

    public function testSign()
    {
        $this->sign();
        $heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransactionSQL->getInfo($this->transaction_id);
        $this->assertTrue($info['signature_technique']);
        $this->assertEquals($info['sha1'], sha1_file("/tmp/{$info['sha1']}"));
        $this->assertEquals($info['file_size'], filesize("/tmp/{$info['sha1']}"));
        $this->getXadesSignature()->verify("/tmp/{$info['sha1']}");
        $this->assertTrue(true);    //Vérifie qu'aucune exception n'est lancée

        $heliosPESValidation = new HeliosPESValidation(HELIOS_XSD_PATH);
        $r = $heliosPESValidation->validate(file_get_contents("/tmp/{$info['sha1']}"));

        $this->assertTrue($r);
    }

    private function sign()
    {
        $this->getHeliosSignatureTechnique()->sign(
            $this->transaction_id,
            __DIR__ . "/../../lib/fixtures/robert_petitpoids.p12",
            "robert_petitpoids",
            $this->getXadesSignatureProperties()
        );
    }

    private function getHeliosSignatureTechnique()
    {
        $heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
        /** @var PesAllerRetriever $pesAllerRetriever */
        $pesAllerRetriever = $this->getObjectInstancier()->get(PesAllerRetriever::class);
        return new HeliosSignatureTechnique(
            $heliosTransactionSQL,
            "/tmp/",
            $this->getXadesSignature(),
            $pesAllerRetriever,
            true
        );
    }

    private function getXadesSignature()
    {
        $xadesSignatureParser = $this->getMockBuilder(XadesSignatureParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $xadesSignatureParser->method("extractXadesSigningTime")
            ->willReturn(new DateTime("2019-01-01"));
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();
        return new XadesSignature(
            XMLSEC1_PATH,
            new PKCS12(),
            new X509Certificate(),
            __DIR__ . "/../../lib/fixtures/validca_for_xades/",
            $xadesSignatureParser,
            new PemCertificateFactory(),
            $verifyPemCertificateFactory->get(__DIR__ . "/../../lib/fixtures/validca_for_xades/")
        );
    }

    private function getXadesSignatureProperties()
    {
        $xadesSignatureProperties = new XadesSignatureProperties();
        $xadesSignatureProperties->claimedRole = "Rôle de test";
        $xadesSignatureProperties->countryName = "France";
        $xadesSignatureProperties->postalCode = "69003";
        $xadesSignatureProperties->city = "Lyon";
        return $xadesSignatureProperties;
    }

    public function testSignModif()
    {
        $file = $this->getFilePathInHeliosUplload();
        file_put_contents($file, "toto");
        $this->setExpectedException(UnrecoverableHeliosSignatureTechniqueException::class, "Le fichier a été modifé depuis son postage sur la plateforme");
        $this->sign();
    }

    private function getFilePathInHeliosUplload()
    {
        $pes_aller = __DIR__ . "/../../helios/fixtures/pes_aller_ok.xml";
        return "/tmp/" . sha1_file($pes_aller);
    }

    public function testDejaSigne()
    {
        $transaction_id = $this->importFile(__DIR__ . "/../../lib/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml");
        $this->getHeliosSignatureTechnique()->sign(
            $transaction_id,
            __DIR__ . "/../../lib/fixtures/robert_petitpoids.p12",
            "robert_petitpoids",
            $this->getXadesSignatureProperties()
        );
        $heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());

        $info = $heliosTransactionSQL->getInfo($transaction_id);
        $this->assertTrue($info['signature_technique']);
        $this->getXadesSignature()->verify("/tmp/{$info['sha1']}");
        $this->assertTrue(true); // vérifie qu'aucune exception n'est lancée
    }

    public function testDejaSigneBadSignature()
    {
        $transaction_id = $this->importFile(__DIR__ . "/../../lib/fixtures/HELIOS_SIMU_ALR2_bad_signature.xml");
        $this->setExpectedException(
            UnrecoverableHeliosSignatureTechniqueException::class,
            "La signature du fichier est invalide"
        );
        $this->getHeliosSignatureTechnique()->sign(
            $transaction_id,
            __DIR__ . "/../../lib/fixtures/robert_petitpoids.p12",
            "robert_petitpoids",
            $this->getXadesSignatureProperties()
        );
    }

    public function testDejaSigneBordereau()
    {
        $transaction_id = $this->importFile(__DIR__ . "/../../lib/fixtures/signature_bordereau2.xml");
        $this->getHeliosSignatureTechnique()->sign(
            $transaction_id,
            __DIR__ . "/../../lib/fixtures/robert_petitpoids.p12",
            "robert_petitpoids",
            $this->getXadesSignatureProperties()
        );
        $heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
        $info = $heliosTransactionSQL->getInfo($transaction_id);
        $this->getXadesSignature()->verify("/tmp/{$info['sha1']}");
        $this->assertTrue(true); //Vérifie qu'aucune exception n'est lancée
    }

    public function testSigneNoID()
    {
        $transaction_id = $this->importFile(__DIR__ . "/../fixtures/pes_no_id.xml");
        $this->getHeliosSignatureTechnique()->sign(
            $transaction_id,
            __DIR__ . "/../../lib/fixtures/robert_petitpoids.p12",
            "robert_petitpoids",
            $this->getXadesSignatureProperties()
        );
        $this->assertTrue(true);
    }
}
