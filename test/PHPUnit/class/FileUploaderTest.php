<?php

declare(strict_types=1);

namespace PHPUnit\class;

use S2lowLegacy\Class\FileUploader;
use S2lowTestCase;

class FileUploaderTest extends S2lowTestCase
{
    public function setUp(): void
    {
        $_FILES = [];
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
        $_FILES['yep']['name'] = 'toto.txt';
        $_FILES['yep']['tmp_name'] = __DIR__ . '/fixtures/toto.txt';
        $_FILES['yep']['error'] = UPLOAD_ERR_OK;
        $_FILES['yep']['size'] = '1024';

        $fileUploader = new FileUploader();

        static::assertTrue($fileUploader->upload('yep'));
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

        static::assertFalse($fileUploader->verifOK("form"));
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

        static::assertFalse($fileUploader->verifOKAll("form"));
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
}
