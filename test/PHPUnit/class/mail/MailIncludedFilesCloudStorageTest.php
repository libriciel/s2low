<?php

declare(strict_types=1);

use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorage;
use S2lowLegacy\Class\TmpFolder;

class MailIncludedFilesCloudStorageTest extends S2lowTestCase
{
    use MailsecUtilitiesTestTrait;

    private function getMailIncludedFilesCloudStorage(): MailIncludedFilesCloudStorage
    {
        return $this->getObjectInstancier()->get(MailIncludedFilesCloudStorage::class);
    }

    public function testGetContainerName(): void
    {
        static::assertEquals(
            MailIncludedFilesCloudStorage::CONTAINER_NAME,
            $this->getMailIncludedFilesCloudStorage()->getContainerName()
        );
    }

    public function testGetAllObjectIdToStore(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        static::assertEquals(
            [$mail_transaction_id],
            $this->getMailIncludedFilesCloudStorage()->getAllObjectIdToStore()
        );
    }

    public function testGetFilePathOnDisk(): void
    {
        $this->getObjectInstancier()->set('mail_files_upload_root', sys_get_temp_dir());
        $mail_transaction_id = $this->createMailTransaction();
        static::assertEquals(
            $this->getArchivePath(sys_get_temp_dir()),
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnDisk($mail_transaction_id)
        );
    }

    public function testGetFilePathOnCloud(): void
    {
        $mail_transaction_id = $this->createMailTransaction();
        static::assertEquals(
            'fn_download_test',
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnCloud($mail_transaction_id)
        );
    }


    public function testGetFilePathOnCloudWithFileOnDiskPath(): void
    {
        static::assertEquals(
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
        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);
        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        static::assertEquals(1, $finder->count());
        static::assertEquals(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }

    /**
     * Dans le cas des mails, le répertoire contenant les fichiers ne correspondant à aucune transaction est dans le
     * même répertoire que les mails eux mêmes.
     * @throws Exception
     */
    public function testFindWithFileWithNoTransac()
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

        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);
        $this->getObjectInstancier()->set('mail_files_without_transac_dir', $mail_files_without_transac_dir);
        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        static::assertEquals(1, $finder->count());
        static::assertEquals(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }

    /**
     * On teste aussi le cas ou le répertoire contenant les fichiers sans transaction n'est pas contenu dans le
     * répertoire contenant tous les fichiers, au cas ou une évolution rationalise l'architecture des répertoires.
     * @throws Exception
     */
    public function testFindWithFileWithNoTransac2()
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

        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);
        $this->getObjectInstancier()->set('mail_files_without_transac_dir', $mail_files_without_transac_dir);
        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        static::assertEquals(1, $finder->count());
        static::assertEquals(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }
}
