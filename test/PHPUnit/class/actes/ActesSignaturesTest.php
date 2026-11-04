<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesSignature;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\TGZExtractor;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesSignaturesTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testSetSignature()
    {
        $tmpFolder = new TmpFolder();
        $tmp_dir = $tmpFolder->create();

        $actesCreator = new ActesCreator(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
        );
        $transaction_id = $actesCreator->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE, __DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz", $tmp_dir);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transction_info = $actesTransactionSQL->getInfo($transaction_id);

        $actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);
        $included_file_id = $actesIncludedFileSQL->addIncludedFile($transction_info['envelope_id'], $transaction_id, 'application/pdf', 42, '034-000000000-20170801-20170803E-AI-1-1_0.xml');


        $actesSignature = $this->getObjectInstancier()->get(ActesSignature::class);
        $actesSignature->setSignature($included_file_id, "ma signature");

        $actes_envelope_info = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class)->getInfo($transction_info['envelope_id']);


        $archivePath = $this->getObjectInstancier()->getParameter('app.actes.files_upload_root') . '/' . $actes_envelope_info['file_path'];

        $result_dir = $tmpFolder->create();
        $tgzExtractor = new TGZExtractor($result_dir);
        $tgzExtractor->extract($archivePath, false);

        $this->assertFileEquals(
            __DIR__ . "/fixtures/fichier-metier-signe.xml",
            $result_dir . "/034-000000000-20170801-20170803E-AI-1-1_0.xml"
        );

        $tmpFolder->delete($tmp_dir);
        $tmpFolder->delete($result_dir);
    }

    /**
     * @throws Exception
     */
    public function testSetSignatureDoesNotRunCommandFromArchiveMember(): void
    {
        $tmpFolder = new TmpFolder();
        $marker = sys_get_temp_dir() . '/s2low_audit037_' . bin2hex(random_bytes(6));
        @unlink($marker);

        // Build an archive that carries, next to the business files, the three files
        // that turn "tar cf - *" into arbitrary command execution.
        $build_dir = $tmpFolder->create();
        $extract = new \Symfony\Component\Process\Process(
            ['tar', 'xzf', __DIR__ . '/fixtures/abc-TACT--000000000--20170803-16.tar.gz', '-C', $build_dir]
        );
        $extract->mustRun();
        file_put_contents($build_dir . '/pwn.sh', 'touch ' . escapeshellarg($marker) . "\n");
        touch($build_dir . '/--checkpoint=1');
        touch($build_dir . '/--checkpoint-action=exec=sh pwn.sh');

        $malicious_archive = $tmpFolder->create() . '/abc-TACT--000000000--20170803-66.tar.gz';
        $members = array_values(array_diff(scandir($build_dir), ['.', '..']));
        $pack = new \Symfony\Component\Process\Process(
            array_merge(['tar', 'czf', $malicious_archive, '-C', $build_dir, '--'], $members)
        );
        $pack->mustRun();

        $tmp_dir = $tmpFolder->create();
        $actesCreator = new ActesCreator(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
        );
        $transaction_id = $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE,
            $malicious_archive,
            $tmp_dir
        );

        $actesTransactionSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $transction_info = $actesTransactionSQL->getInfo($transaction_id);

        $actesIncludedFileSQL = self::getContainer()->get(ActesIncludedFileSQL::class);
        $included_file_id = $actesIncludedFileSQL->addIncludedFile(
            $transction_info['envelope_id'],
            $transaction_id,
            'application/pdf',
            42,
            '034-000000000-20170801-20170803E-AI-1-1_0.xml'
        );

        try {
            self::getContainer()->get(ActesSignature::class)->setSignature($included_file_id, 'ma signature');

            static::assertFileDoesNotExist($marker, 'The archive member name was executed as a command');
        } finally {
            @unlink($marker);
            $tmpFolder->delete($build_dir);
            $tmpFolder->delete($tmp_dir);
            @unlink($malicious_archive);
        }
    }

    public function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    private function getActesSignature(
        ActesIncludedFileSQL $actesIncludedFileSQL,
        ActesTransactionsSQL $actesTransactionSQL,
    ): ActesSignature {
        $actesRetriever = $this->getActesRetriever();

        return new ActesSignature(
            $actesIncludedFileSQL,
            $actesTransactionSQL,
            self::getContainer()->get(ActesEnvelopeSQL::class),
            $actesRetriever
        );
    }

    private function getActesRetriever(): ActesRetriever
    {
        return new ActesRetriever(
            $this->tmpPathFolder,
            self::getContainer()->get(OpenStackSwiftWrapper::class),
            $this->s2lowLogger
        );
    }
}
