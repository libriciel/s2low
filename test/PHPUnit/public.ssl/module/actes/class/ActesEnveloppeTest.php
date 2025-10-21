<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Services\FileDataProvider;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\TmpFolder;

class ActesEnveloppeTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testSendFile()
    {
        $tmpFolder = new TmpFolder();
        $my_tmp_folder = $tmpFolder->create();
        $fileName = 'test.txt';
        $file_path = "$my_tmp_folder/$fileName";
        file_put_contents($file_path, "foo");
        $actesRetriever = new LocalFileResolver(
            self::getContainer()->get(ActesEnvelopeSQL::class),
            $my_tmp_folder
        );
        self::getContainer()->set('app.localFileResolver.acte_enveloppe', $actesRetriever);



        $enveloppeId = $this->createEnveloppe($fileName);
        $filePathResolved = $actesRetriever->getFullPath($enveloppeId);
        file_put_contents($filePathResolved, "toto");

        $actesEnvelope = new ActesEnvelope();
        $actesEnvelope->set('file_path', "test.txt");
        $this->expectOutputRegex("#toto#");
        $actesEnvelope->sendFile($enveloppeId);
        $tmpFolder->delete($my_tmp_folder);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
