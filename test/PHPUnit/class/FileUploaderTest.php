<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use S2lowLegacy\Class\FileUploader;
use S2lowLegacy\Class\TmpFolder;
use S2lowTestCase;

class FileUploaderTest extends S2lowTestCase
{
    private TmpFolder $tmpFolder;

    protected function setUp(): void
    {
        parent::setUp();
        $_FILES = [];
        $this->tmpFolder = new TmpFolder();
    }
    public function testUploadInexistingFile(): void
    {
        $fileUploader = new FileUploader();
        static::assertFalse($fileUploader->upload('nope'));
        static::assertEquals(
            "Il n'y a pas de fichier à charger sur le serveur",
            $fileUploader->getLastError()
        );
    }

    public function testUploadOkFile(): void
    {
        $_FILES['yep']['name'] = 'titi.txt';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $fileUploader = new FileUploader();

        static::assertTrue($fileUploader->upload('yep'));
        static::assertSame('titi.txt', $fileUploader->getFileName());
        // C'est le contenu de $_FILES['yep']['size'] = '1024', non la taille réelle du fichier ...
        static::assertSame('1024', $fileUploader->getFileSize());
        static::assertSame('contenuToto', $fileUploader->getLigne());
        static::assertFalse($fileUploader->getLigne());
    }

    /**
     * @throws Exception
     */
    public function testUploadExistingFile(): void
    {
        $_FILES['yep']['name'] = 'toto.txt';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $testDirectory = $this->tmpFolder->create();
        copy(__DIR__ . '/fixtures/toto.txt', "$testDirectory/toto.txt");

        $fileUploader = new FileUploader();
        $fileUploader->setDestinationDirectory("$testDirectory/");

        static::assertFalse($fileUploader->upload('yep'));
        static::assertSame(
            'Le fichier toto.txt existe déjà sur le serveur',
            $fileUploader->getLastError()
        );

        $this->tmpFolder->delete($testDirectory);
    }

    /**
     * @throws Exception
     */
    public function testUploadWrongDirectory(): void
    {
        $_FILES['yep']['name'] = 'toto.txt';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $testDirectory = $this->tmpFolder->create();

        $fileUploader = new FileUploader();
        $fileUploader->setDestinationDirectory("$testDirectory/nochancethisisadir");

        static::assertFalse($fileUploader->upload('yep'));
        static::assertSame(
            'Impossible de recopier le fichier sur le serveur ',
            $fileUploader->getLastError()
        );

        $this->tmpFolder->delete($testDirectory);
    }

    public function testUploadExtension(): void
    {
        $_FILES['yep']['name'] = 'toto.txt';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $fileUploader = new FileUploader();

        $fileUploader->upload('yep');

        static::assertEquals(
            'txt',
            $fileUploader->getExtension()
        );
    }

    public function testUploadExtensionWithAccentedCharacters(): void
    {
        $_FILES['yep']['name'] = 'éâô.txt';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $fileUploader = new FileUploader();

        $fileUploader->upload('yep');

        static::assertEquals(
            'txt',
            $fileUploader->getExtension()
        );
    }

    public function testUploadForbiddenExtension(): void
    {
        $_FILES['nope']['name'] = 'toto.asp';
        $_FILES['nope']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['nope']['error'] = UPLOAD_ERR_OK;
        $_FILES['nope']['size'] = '1024';

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->upload('nope'));

        static::assertEquals(
            'Le fichier toto.asp contient une extension interdite',
            $fileUploader->getLastError()
        );
    }

    public function testUploadDisabledForbiddenExtension(): void
    {
        $_FILES['yep']['name'] = 'toto.asp';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $fileUploader = new FileUploader();
        $fileUploader->disableForbidenExtension();

        static::assertTrue($fileUploader->upload('yep'));
    }
    public function testVerifOkNoFiles(): void
    {
        $_FILES = [];

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOK('nope'));
        static::assertEquals(
            "Il n'y a pas de fichier à charger sur le serveur",
            $fileUploader->getLastError()
        );
    }

    public function testVerifOkError(): void
    {
        $_FILES['form']['error'] = UPLOAD_ERR_NO_FILE;

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOK('form'));
        static::assertEquals(
            "Aucun fichier n'a été présenté",
            $fileUploader->getLastError()
        );
    }

    public function testVerifOkEmptyFile(): void
    {
        $_FILES['form']['name'] = 'vide.pdf';
        $_FILES['form']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['form']['error'] = UPLOAD_ERR_OK;
        $_FILES['form']['size'] = '0';

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOK('form'));
        static::assertEquals(
            'Le fichier vide.pdf semble vide',
            $fileUploader->getLastError()
        );
    }

    public function testVerifOk(): void
    {
        $_FILES['form']['name'] = 'toto.txt';
        $_FILES['form']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['form']['error'] = UPLOAD_ERR_OK;
        $_FILES['form']['size'] = '1024';

        $fileUploader = new FileUploader();

        static::assertTrue($fileUploader->verifOK('form'));
    }

    public function testVerifOkAllNoFiles(): void
    {
        $_FILES = [];

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOKAll('nope'));
        static::assertEquals(
            "Il n'y a pas de fichier à charger sur le serveur",
            $fileUploader->getLastError()
        );
    }

    public function testVerifOkAllError(): void
    {
        $_FILES['form']['error'][] = UPLOAD_ERR_NO_FILE;

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOKAll('form'));
        static::assertEquals(
            "Aucun fichier n'a été présenté",
            $fileUploader->getLastError()
        );
    }

    public function testVerifOkAllEmptyFile(): void
    {
        $_FILES['form']['name'][] = 'vide.pdf';
        $_FILES['form']['tmp_name'][] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['form']['error'][] = UPLOAD_ERR_OK;
        $_FILES['form']['size'][] = '0';

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOKAll('form'));
        static::assertEquals(
            'Le fichier vide.pdf semble vide',
            $fileUploader->getLastError()
        );
    }

    public function testVerifOkAll(): void
    {
        $_FILES['form']['name'][] = 'toto.txt';
        $_FILES['form']['tmp_name'][] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['form']['error'][] = UPLOAD_ERR_OK;
        $_FILES['form']['size'][] = '1024';

        $fileUploader = new FileUploader();

        static::assertTrue($fileUploader->verifOKAll('form'));
    }

    /**
     * @dataProvider errors
     */
    public function testErrorMessages(int $error, string $message): void
    {
        $_FILES['form']['name'][] = 'toto.txt';
        $_FILES['form']['tmp_name'][] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['form']['error'][] = $error;
        $_FILES['form']['size'][] = '1024';

        $fileUploader = new FileUploader();

        static::assertFalse($fileUploader->verifOKAll('form'));
        static::assertEquals($message, $fileUploader->getLastError());
    }

    public function errors(): iterable
    {
        yield [ UPLOAD_ERR_INI_SIZE,
            'La taille du fichier excède la taille maximum (' . ini_get('upload_max_filesize') . ')'];
        yield [ UPLOAD_ERR_FORM_SIZE, 'Le fichier dépasse la taille limite autorisée par le formulaire'];
        yield [ UPLOAD_ERR_PARTIAL, "Le fichier n'a été que partiellement reçu"];
        yield [ UPLOAD_ERR_NO_FILE, "Aucun fichier n'a été présenté"];
        yield [ UPLOAD_ERR_NO_TMP_DIR, "Erreur de configuration : le répertoire temporaire n'existe pas"];
        yield [UPLOAD_ERR_CANT_WRITE, "Erreur de configuration : Impossible d'écrire dans le répertoire temporaire"];
        yield [UPLOAD_ERR_EXTENSION, "Une extension PHP empeche l'upload du fichier!"];
        yield [ 256678, 'Erreur lors du chargement du fichier (code 256678)'];
    }
}
