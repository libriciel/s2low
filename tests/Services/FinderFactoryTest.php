<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use S2low\Services\FinderFactory;
use Symfony\Component\Finder\Finder;

/**
 * @covers \S2low\Services\FinderFactory
 */
final class FinderFactoryTest extends TestCase
{
    private string $vfsRoot;
    private FinderFactory $finderFactory;

    protected function setUp(): void
    {
        vfsStream::setup('test');
        $this->vfsRoot = vfsStream::url('test');

        // Création des répertoires pour chaque type de finder
        mkdir($this->vfsRoot . '/actes', 0777, true);
        mkdir($this->vfsRoot . '/helios', 0777, true);
        mkdir($this->vfsRoot . '/responses', 0777, true);
        mkdir($this->vfsRoot . '/mailsec', 0777, true);

        $this->finderFactory = new FinderFactory(
            $this->vfsRoot . '/actes',
            $this->vfsRoot . '/helios',
            $this->vfsRoot . '/responses',
            $this->vfsRoot . '/mailsec',
            '*_acquit_*.xml',
            '*_retour_*.xml'
        );
    }

    /**
     * Vérifie que createActeEnveloppeFinder retourne une instance de Finder
     */
    public function testCreateActeEnveloppeFinderReturnsFinderInstance(): void
    {
        $finder = $this->finderFactory->createActeEnveloppeFinder();

        $this->assertInstanceOf(Finder::class, $finder);
    }

    /**
     * Vérifie que le finder pour les actes ne recherche que les fichiers .tar.gz
     */
    public function testCreateActeEnvelopeFinderSearchesTarGzFiles(): void
    {
        // Création des fichiers de test
        file_put_contents($this->vfsRoot . '/actes/test1.tar.gz', 'test');
        file_put_contents($this->vfsRoot . '/actes/test2.tar.gz', 'test');
        file_put_contents($this->vfsRoot . '/actes/test3.pdf', 'test');

        $finder = $this->finderFactory->createActeEnveloppeFinder();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(2, $files);
        $this->assertContains('test1.tar.gz', $files);
        $this->assertContains('test2.tar.gz', $files);
        $this->assertNotContains('test3.pdf', $files);
    }

    /**
     * Vérifie que createPesAllerFinder retourne une instance de Finder
     */
    public function testCreatePesAllerFinderReturnsFinderInstance(): void
    {
        $finder = $this->finderFactory->createPesAllerFinder();

        $this->assertInstanceOf(Finder::class, $finder);
    }

    /**
     * Vérifie que le finder pour PES Aller recherche tous les fichiers sans filtrage
     */
    public function testCreatePesAllerFinderSearchesAllFiles(): void
    {
        // Création des fichiers de test
        file_put_contents($this->vfsRoot . '/helios/file1.xml', 'test');
        file_put_contents($this->vfsRoot . '/helios/file2.pdf', 'test');

        $finder = $this->finderFactory->createPesAllerFinder();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(2, $files);
    }

    /**
     * Vérifie que createPesRetourFinder retourne une instance de Finder
     */
    public function testCreatePesRetourFinderReturnsFinderInstance(): void
    {
        $finder = $this->finderFactory->createPesRetourFinder();

        $this->assertInstanceOf(Finder::class, $finder);
    }

    /**
     * Vérifie que le finder pour PES Retour filtre les fichiers selon le pattern *_retour_*.xml
     */
    public function testCreatePesRetourFinderSearchesWithPattern(): void
    {
        // Création de fichiers correspondant et ne correspondant pas au pattern
        file_put_contents($this->vfsRoot . '/responses/123_retour_456.xml', 'test');
        file_put_contents($this->vfsRoot . '/responses/other_file.xml', 'test');

        $finder = $this->finderFactory->createPesRetourFinder();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(1, $files);
        $this->assertContains('123_retour_456.xml', $files);
    }

    /**
     * Vérifie que createPesAcquitFinder retourne une instance de Finder
     */
    public function testCreatePesAcquitFinderReturnsFinderInstance(): void
    {
        $finder = $this->finderFactory->createPesAcquitFinder();

        $this->assertInstanceOf(Finder::class, $finder);
    }

    /**
     * Vérifie que le finder pour PES Acquit filtre les fichiers selon le pattern *_acquit_*.xml
     */
    public function testCreatePesAcquitFinderSearchesWithPattern(): void
    {
        // Création de fichiers correspondant et ne correspondant pas au pattern
        file_put_contents($this->vfsRoot . '/responses/123_acquit_456.xml', 'test');
        file_put_contents($this->vfsRoot . '/responses/other_file.xml', 'test');

        $finder = $this->finderFactory->createPesAcquitFinder();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(1, $files);
        $this->assertContains('123_acquit_456.xml', $files);
    }

    /**
     * Vérifie que createMailSecFinder retourne une instance de Finder
     */
    public function testCreateMailSecFinderReturnsFinderInstance(): void
    {
        $finder = $this->finderFactory->createMailSecFinder();

        $this->assertInstanceOf(Finder::class, $finder);
    }

    /**
     * Vérifie que le finder pour MailSec ne recherche que les fichiers mail.zip
     */
    public function testCreateMailSecFinderSearchesMailZipFiles(): void
    {
        // Création des fichiers de test
        file_put_contents($this->vfsRoot . '/mailsec/mail.zip', 'test');
        file_put_contents($this->vfsRoot . '/mailsec/other.zip', 'test');

        $finder = $this->finderFactory->createMailSecFinder();

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getFilename();
        }

        $this->assertCount(1, $files);
        $this->assertContains('mail.zip', $files);
    }
}
