<?php

class XadesSignatureTest extends PHPUnit_Framework_TestCase {

	public function testSignFileNotExists(){
		$tmp_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$this->setExpectedException("Exception","failed to load external entity");
		$this->sign($tmp_file);
	}

	/**
	 * @param $file_to_sign
	 * @return string
	 * @throws XadesSignatureHasSignatureException
	 */
	private function sign($file_to_sign){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xadesSignature = $this->getXadesSignature();
		$xadesSignature->sign($file_to_sign,__DIR__."/fixtures/robert_petitpoids.p12","robert_petitpoids",$signed_file,$this->getXadesSignatureProperties());
		return $signed_file;
	}

	private function getXadesSignature(){
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();
		$xadesSignature = new XadesSignature(
			XMLSEC1_PATH,
			new PKCS12(),
			new X509Certificate(),
			__DIR__ . "/fixtures/validca_for_xades/",
            new XadesSignatureParser(),
            new PemCertificateFactory(),
            $verifyPemCertificateFactory->get(__DIR__ . "/fixtures/validca_for_xades/")
		);
		return $xadesSignature;
	}

	private function getXadesSignatureProperties(){
		$xadesSignatureProperties = new XadesSignatureProperties();
		$xadesSignatureProperties->city = "Paris";
		$xadesSignatureProperties->postalCode = "75008";
		$xadesSignatureProperties->countryName = "France";
		$xadesSignatureProperties->claimedRole = "Test Tiers de télétransmission";
		return $xadesSignatureProperties;
	}

    /**
     * @param $filename
     * @throws XadesSignatureHasSignatureException
     * @dataProvider filesProvider
     */
	public function testSign(string $filename){
		$signed_file = $this->sign($filename);
        $xadesSignatureParser = $this->getMockBuilder(XadesSignatureParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $xadesSignatureParser->method("extractXadesSigningTime")
            ->willReturn(new DateTime("2019-01-01"));
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();
        $xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            new PKCS12(),
            new X509Certificate(),
            __DIR__ . "/fixtures/validca_for_xades/",
            $xadesSignatureParser,
            new PemCertificateFactory(),
            $verifyPemCertificateFactory->get(__DIR__ . "/fixtures/validca_for_xades/")
        );
		$xadesSignature->verify($signed_file); //Test no exception is thrown
        $this->assertTrue(true);
	}

	public function filesProvider(){
	    yield "with minimal file" => [__DIR__."/fixtures/test.xml"];
        yield "with PES file" => [__DIR__."/fixtures/HELIOS_SIMU_ALR2_1444811220_681372666.xml"];
    }

	private function verify($file_to_verify){
		$xadesSignature = $this->getXadesSignature();
        $xadesSignature->verify($file_to_verify);
		$this->assertTrue(true); //test no exception is thrown;
	}

	public function testSignWithoutDocumentElementId(){
		$this->setExpectedException("XadesSignatureNoIDException","Le document XML ne contient pas d'Id");
		$this->sign(__DIR__."/fixtures/test-no-id.xml");
	}

	public function testBadPassword(){
		$signed_file = sys_get_temp_dir()."/".uniqid("phpunit");
		$xadesSignature = $this->getXadesSignature();
		$this->setExpectedException("Exception","Impossible de lire le certificat PKCS#12");
		$xadesSignature->sign(__DIR__."/fixtures/test.xml",__DIR__."/fixtures/robert_petitpoids.p12","bad password",$signed_file,$this->getXadesSignatureProperties());
	}

	public function testTargetSignature(){
		$signed_file = $this->sign(__DIR__."/fixtures/test.xml");
		$xml = simplexml_load_file($signed_file);
		$id = strval($xml->children(XadesSignature::NS_DS_URI)->Signature->attributes()->Id);
		$targetId = strval($xml->children(XadesSignature::NS_DS_URI)->Signature->Object->children(XadesSignature::NS_XAD_URI)->QualifyingProperties->attributes()["Target"]);
		$this->assertEquals("#$id",$targetId);
	}

	/**
	 * @throws XadesSignatureHasSignatureException
	 */
	public function testOutputFileNotWritable(){
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$xadesSignature = $this->getXadesSignature();
		$this->setExpectedException("Exception","Erreur (1) lors de la signature technique");
		$xadesSignature->sign(
			__DIR__."/fixtures/test.xml",
			__DIR__."/fixtures/robert_petitpoids.p12",
			"robert_petitpoids",
			$testStreamUrl."/signed.xml",
			$this->getXadesSignatureProperties())
		;
		$xadesSignature->sign(
			__DIR__."/fixtures/test.xml",
			__DIR__."/../fixtures/timestamp_certificates/s2low_timestamp_cert.pem.p12",
			"",
			$testStreamUrl."/signed.xml",
			$this->getXadesSignatureProperties())
		;
	}

	public function testVerifyNOCA(){
		$xadesSignature = $this->getXadesSignature();
		$xadesSignature->verify(__DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml");
		$this->assertTrue(true);    //Test no exception is thrown
	}

	public function testHasSignature(){
		$this->expectException("XadesSignatureHasSignatureException");
		$signed_file = $this->sign(__DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml");
		$this->verify($signed_file);
	}

	public function testVerifSignatureNotGlobale() {
		$this->verify(__DIR__ . "/fixtures/signature_bordereau2.xml");
	}

	public function testVerifSignatureNotGlobaleBad() {
		$xadesSignature = $this->getXadesSignature();
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Impossible d'affirmer que la signature correspond au fichier");
		$xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau_bad.xml");
	}

	public function testDeleteSignature(){
		$file = __DIR__."/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml";

		$result =  "/tmp/result.xml";
		$xadesSignature = $this->getXadesSignature();
		$this->assertTrue($xadesSignature->isSigned($file));
		$xadesSignature->deleteSignature($file,$result);
		$this->assertFalse($xadesSignature->isSigned($result));
	}
    /** @dataProvider datesProvider */

    public function testDateEffect(DateTime $dateTime, bool $expected)
    {
        $xadesSignatureParser = $this->getMockBuilder(XadesSignatureParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $xadesSignatureParser->method("extractXadesSigningTime")
            ->willReturn($dateTime);
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();
        $xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            new PKCS12(),
            new X509Certificate(),
            __DIR__ . "/fixtures/validca_for_xades/",
            $xadesSignatureParser,
            new PemCertificateFactory(),
            $verifyPemCertificateFactory->get(__DIR__ . "/fixtures/validca_for_xades/")
        );

        $verify = true;
        try{
            $xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau.xml");
        } catch (Exception $e){
            $verify =false;
        }
        $this->assertEquals(
            $expected,
            $verify
        );
    }

    public function datesProvider(){
        return [
            // Date de vérification                  validité attendue
            [new DateTime("2012-11-05T11:33:13Z"),false],    // Limite basse du certificat AC_ADULLACT_ROOT_G3
            [new DateTime("2016-11-07T11:03:01Z"),true],     // Date de la signature
            [new DateTime("2022-11-05T11:33:15Z"),false]     // Limite haute du certificat AC_ADULLACT_ROOT_G3
        ];
    }

    public function testcheckCertificateWithOpenSSLThrowException(){
        $xadesSignatureParser = $this->getMockBuilder(XadesSignatureParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificate = $this->getMockBuilder(VerifyPemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificate->method("checkCertificateWithOpenSSL")
            ->willThrowException(new Exception("Test"));

        $verifyPemCertificateFactory = $this->getMockBuilder(VerifyPemCertificateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificateFactory->method("get")->willReturn($verifyPemCertificate);

        $xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            new PKCS12(),
            new X509Certificate(),
            __DIR__ . "/fixtures/validca_for_xades/",
            $xadesSignatureParser,
            new PemCertificateFactory(),
            $verifyPemCertificateFactory->get(__DIR__ . "/fixtures/validca_for_xades/")
        );

        $filesBeforeVerify = glob('/tmp/s2low_xades_*');
        try{
            $xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau.xml");
        } catch (Exception $e){
            $filesAfterVerify = glob('/tmp/s2low_xades_*');
            $this->assertSameSize(
                $filesAfterVerify,
                $filesBeforeVerify
            );
        }
    }
}
