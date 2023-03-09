<?php

use S2lowLegacy\Class\FileUploader;

class FileUploaderTest extends S2lowTestCase
{
    public function testUploadInexistingFile()
    {
        $fileUploader = new FileUploader();
        $this->assertFalse(
            $fileUploader->upload("nope")
        );
        $this->assertEquals(
            "Il n'y a pas de fichier à charger sur le serveur",
            $fileUploader->getLastError()
        );
    }

    public function testUploadOkFile()
    {
        $_FILES["yep"]['name'] = "toto.txt";
        $_FILES["yep"]['tmp_name'] = __DIR__ . "/fixtures/toto.txt";
        $_FILES["yep"]['error'] = UPLOAD_ERR_OK;
        $_FILES["yep"]['size'] = "1024";

        $fileUploader = new FileUploader();

        $this->assertTrue(
            $fileUploader->upload("yep")
        );
    }

    public function testUploadExtension()
    {
        $_FILES["yep"]['name'] = "toto.txt";
        $_FILES["yep"]['tmp_name'] = __DIR__ . "/fixtures/toto.txt";
        $_FILES["yep"]['error'] = UPLOAD_ERR_OK;
        $_FILES["yep"]['size'] = "1024";

        $fileUploader = new FileUploader();

        $fileUploader->upload("yep");

        $this->assertEquals(
            "txt",
            $fileUploader->getExtension()
        );
    }

    public function testUploadExtensionWithAccentedCharacters()
    {
        $_FILES["yep"]['name'] = "éâô.txt";
        $_FILES["yep"]['tmp_name'] = __DIR__ . "/fixtures/toto.txt";
        $_FILES["yep"]['error'] = UPLOAD_ERR_OK;
        $_FILES["yep"]['size'] = "1024";

        $fileUploader = new FileUploader();

        $fileUploader->upload("yep");

        $this->assertEquals(
            "txt",
            $fileUploader->getExtension()
        );
    }

    public function testUploadForbiddenExtension()
    {
        $_FILES["nope"]['name'] = "toto.asp";
        $_FILES["nope"]['tmp_name'] = __DIR__ . "/fixtures/toto.txt";
        $_FILES["nope"]['error'] = UPLOAD_ERR_OK;
        $_FILES["nope"]['size'] = "1024";

        $fileUploader = new FileUploader();

        $this->assertFalse(
            $fileUploader->upload("nope")
        );

        $this->assertEquals(
            "Le fichier toto.asp contient une extension interdite",
            $fileUploader->getLastError()
        );
    }
}
