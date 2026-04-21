<?php

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Boot\S2lowBootstrap;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class S2lowBootstrapTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->letsencryptPath = "/tmp/" . uniqid("letsencrypt_");
        mkdir($this->letsencryptPath);
        $this->apachePath = "/tmp/" . uniqid("apache_");
        mkdir($this->apachePath);
    }
    public function testExistingCertificate()
    {
        # Si une clé privée existe déjà dans le répertoire apache, on utilise celle-ci et rien n'est créé.
        $S2lowBootstrap = new S2lowBootstrap(
            $this->getMockBuilder(\S2lowLegacy\Lib\SQLQuery::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(\S2lowLegacy\Controller\PostgreSQLController::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Application::class)->disableOriginalConstructor()->getMock()
        );

        file_put_contents($this->apachePath . "/privKeyFilename", "privKeyContent");

        $S2lowBootstrap->installSelfSignedCertificateIfNoneExists(
            "testhost",
            "privKeyFilename",
            "fullchainFilename",
            $this->letsencryptPath,
            $this->apachePath
        );

        $this->assertEquals(file_get_contents($this->apachePath . "/privKeyFilename"), "privKeyContent");
    }

    public function testExistingLetsEncryptCertificatesWithoutLinks()
    {
        # Si le fichier $apacheSSLPath/$privKeyFilename n'existe pas mais
        # "$letsencrypt_cert_path/$hostname/$privKeyFilename" existe, on créé des liens symboliques depuis le répertoire
        # $letsencrypt_cert_path
        # Normalement, ce cas ne devrait pas avoir lieu ?? Les liens sont créés dans docker-s2low-entrypoint...

        $S2lowBootstrap = new S2lowBootstrap(
            $this->getMockBuilder(\S2lowLegacy\Lib\SQLQuery::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(\S2lowLegacy\Controller\PostgreSQLController::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Application::class)->disableOriginalConstructor()->getMock()
        );

        mkdir($this->letsencryptPath . "/testhost");
        file_put_contents($this->letsencryptPath . "/testhost/privKeyFilename", "privKeyContent");
        file_put_contents($this->letsencryptPath . "/testhost/fullchainFilename", "fullchainContent");

        $S2lowBootstrap->installSelfSignedCertificateIfNoneExists(
            "testhost",
            "privKeyFilename",
            "fullchainFilename",
            $this->letsencryptPath,
            $this->apachePath
        );

        $this->assertEquals(file_get_contents($this->apachePath . "/privKeyFilename"), "privKeyContent");
        $this->assertEquals(file_get_contents($this->apachePath . "/fullchainFilename"), "fullchainContent");
    }

    public function testNoExistingCertificates()
    {
        # Si aucun certificat n'existe, la clé privée et la clé publique sont créés dans $apacheSSLPath
        $S2lowBootstrap = new S2lowBootstrap(
            $this->getMockBuilder(\S2lowLegacy\Lib\SQLQuery::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(\S2lowLegacy\Controller\PostgreSQLController::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Application::class)->disableOriginalConstructor()->getMock()
        );

        $S2lowBootstrap->installSelfSignedCertificateIfNoneExists(
            "testhost",
            "privKeyFilename",
            "fullchainFilename",
            $this->letsencryptPath,
            $this->apachePath
        );

        $certificat = file_get_contents($this->apachePath . "/fullchainFilename");
        $this->assertEquals(
            openssl_x509_parse($certificat)["subject"]["CN"],
            "testhost"
        );
    }
}
