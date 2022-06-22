<?php


use PHPUnit\Framework\TestCase;

class RgsCertificateTest extends TestCase {

	/**
	 * @var RgsCertificate
	 */

	private $rgsCertificate;

	protected function setUp() : void {
		parent::setUp();
		$validca_path = __DIR__."/fixtures/test";
		$this->rgsCertificate = new RgsCertificate(OPENSSL_PATH,$validca_path);
	}

    /**
     * @throws Exception
     */
	public function testVerify(){
		$x509_pem_certificate = file_get_contents(__DIR__."/fixtures/test/MyRootCA.pem");
		$this->assertTrue($this->rgsCertificate->isRgsCertificate($x509_pem_certificate));
	}

    /**
     * @throws Exception
     */
	public function testVerifyBadCertificat(){
		$x509_pem_certificate = file_get_contents(__DIR__."/fixtures/clean_pem.pem");
		$this->assertFalse($this->rgsCertificate->isRgsCertificate($x509_pem_certificate));
		$this->assertMatchesRegularExpression("#unable to get local issuer certificate#",$this->rgsCertificate->getLastMessage());
	}

    /**
     * @throws Exception
     */

    public function testIsRgsConnexionAutosignedRoot(){
        $x509_pem_certificate = file_get_contents(__DIR__."/../core/fixtures/CertAutosignedRoot/test-s2low-demo-s2low.pem");
        $x509_ca_certificate = file_get_contents(__DIR__."/../core/fixtures/CertAutosignedRoot/AC_LIBRICIEL_RACINE_G1_CHAIN.pem");
        $x509_intermediate_certificate = file_get_contents(__DIR__."/../core/fixtures/CertAutosignedRoot/AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem");

        $ca_path_without_root=__DIR__."/../controller/fixtures/validca";

        $rgsCertificateToTest = new RgsCertificate(OPENSSL_PATH,$ca_path_without_root);
        $this->assertFalse($rgsCertificateToTest->isRgsCertificate($x509_pem_certificate,$x509_intermediate_certificate.$x509_ca_certificate));
    }

    /**
     * @throws Exception
     */
    public function testIsRgsConnexionAutosignedRootInCA(){
        $x509_pem_certificate = file_get_contents(__DIR__."/../core/fixtures/CertAutosignedRoot/test-s2low-demo-s2low.pem");
        $x509_ca_certificate = file_get_contents(__DIR__."/../core/fixtures/CertAutosignedRoot/AC_LIBRICIEL_RACINE_G1_CHAIN.pem");
        $x509_intermediate_certificate = file_get_contents(__DIR__."/../core/fixtures/CertAutosignedRoot/AC_LIBRICIEL_PERSONNEL_G2_CHAIN.pem");

        $ca_path_with_root=__DIR__."/../core/fixtures/CertAutosignedRoot/CA";

        $rgsCertificateToTest = new RgsCertificate(OPENSSL_PATH,$ca_path_with_root);
        $this->assertTrue($rgsCertificateToTest->isRgsCertificate($x509_pem_certificate,$x509_intermediate_certificate.$x509_ca_certificate));
    }
}