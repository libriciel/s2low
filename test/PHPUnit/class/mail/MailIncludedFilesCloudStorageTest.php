<?php

declare(strict_types=1);

namespace PHPUnit\class\mail;

use Exception;
use MailsecUtilitiesTestTrait;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorable;
use S2lowLegacy\Class\mailsec\MailTransactionSQL;
use S2lowLegacy\Class\TmpFolder;
use S2lowTestCase;

class MailIncludedFilesCloudStorageTest extends S2lowTestCase
{
    use MailsecUtilitiesTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mail_files_upload_root = $this->tmpPathFolder;
        $this->mail_files_without_transac_dir = $this->secondTmpPathFolder;
    }

    private function getMailIncludedFilesCloudStorage(): MailIncludedFilesCloudStorable
    {
        return new MailIncludedFilesCloudStorable(
            self::getContainer()->get(MailTransactionSQL::class),
            $this->mail_files_upload_root,
            $this->mail_files_without_transac_dir
        );
    }

    public function testGetContainerName(): void
    {
        static::assertSame(
            MailIncludedFilesCloudStorable::CONTAINER_NAME,
            $this->getMailIncludedFilesCloudStorage()->getContainerName()
        );
    }

    public function testGetAllObjectIdToStore(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        static::assertSame(
            [$mail_transaction_id],
            $this->getMailIncludedFilesCloudStorage()->getAllObjectIdToStore()
        );
    }

    public function testGetFilePathOnDisk(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        static::assertSame(
            $this->getArchivePath($this->tmpPathFolder),
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnDisk($mail_transaction_id)
        );
    }

    public function testGetFilePathOnCloud(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        static::assertSame(
            'fn_download_test',
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnCloud($mail_transaction_id)
        );
    }


    public function testGetFilePathOnCloudWithFileOnDiskPath(): void
    {
        static::assertSame(
            $this->fn_download_payload,
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnCloudWithFileOnDiskPath(
                $this->getArchivePath()
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testSetNotAvailable(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->getMailIncludedFilesCloudStorage()->setNotAvailable($mail_transaction_id);
        $info = $this->getSQLQuery()->queryOne('SELECT * FROM mail_transaction WHERE id=?', $mail_transaction_id);
        static::assertTrue($info['not_available']);
    }

    public function testSetInCloud(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->getMailIncludedFilesCloudStorage()->setInCloud($mail_transaction_id);
        $info = $this->getSQLQuery()->queryOne('SELECT * FROM mail_transaction WHERE id=?', $mail_transaction_id);
        static::assertTrue($info['is_in_cloud']);
    }

    /**
     * @throws Exception
     */
    public function testFind()
    {
        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . '/' . $this->fn_download_payload);
        file_put_contents(
            $this->getArchivePath($mail_files_upload_root),
            'test'
        );
        $this->mail_files_upload_root = $mail_files_upload_root;

        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        static::assertSame(1, $finder->count());
        static::assertSame(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }

    /**
     * Dans le cas des mails, le répertoire contenant les fichiers ne correspondant à aucune transaction est dans le
     * même répertoire que les mails eux mêmes.
     * @throws Exception
     */
    public function testFindWithFileWithNoTransac(): void
    {
        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . '/' . $this->fn_download_payload);
        file_put_contents(
            $this->getArchivePath($mail_files_upload_root),
            'test'
        );

        $mail_files_without_transac_dir = $mail_files_upload_root . '/sans_transaction/';
        mkdir($mail_files_without_transac_dir . 'test/', 0777, true);
        file_put_contents($mail_files_without_transac_dir . 'test/mail.zip', 'test2');

        $this->mail_files_upload_root = $mail_files_upload_root;
        $this->mail_files_without_transac_dir = $mail_files_without_transac_dir;
        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        static::assertSame(1, $finder->count());
        static::assertSame(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }

    /**
     * On teste aussi le cas ou le répertoire contenant les fichiers sans transaction n'est pas contenu dans le
     * répertoire contenant tous les fichiers, au cas ou une évolution rationalise l'architecture des répertoires.
     * @throws Exception
     */
    public function testFindWithFileWithNoTransacToDirOutside(): void
    {
        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . '/' . $this->fn_download_payload);
        file_put_contents(
            $this->getArchivePath($mail_files_upload_root),
            'test'
        );

        $mail_files_without_transac_dir = $tmpFolder->create();
        mkdir($mail_files_without_transac_dir . 'test/', 0777, true);
        file_put_contents($mail_files_without_transac_dir . 'test/mail.zip', 'test2');
        $this->mail_files_upload_root = $mail_files_upload_root;
        $this->mail_files_without_transac_dir = $mail_files_without_transac_dir;

        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        static::assertSame(1, $finder->count());
        static::assertSame(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }
}
