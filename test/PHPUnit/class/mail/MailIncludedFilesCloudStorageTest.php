<?php

class MailIncludedFilesCloudStorageTest extends S2lowTestCase
{
    use MailsecUtilitiesTestTrait;

    private function getMailIncludedFilesCloudStorage()
    {
        return $this->getObjectInstancier()->get(MailIncludedFilesCloudStorage::class);
    }

    public function testGetContainerName()
    {
        $this->assertEquals(
            MailIncludedFilesCloudStorage::CONTAINER_NAME,
            $this->getMailIncludedFilesCloudStorage()->getContainerName()
        );
    }

    public function testGetAllObjectIdToStore()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->assertEquals(
            [$mail_transaction_id],
            $this->getMailIncludedFilesCloudStorage()->getAllObjectIdToStore()
        );
    }

    public function testGetFilePathOnDisk()
    {
        $this->getObjectInstancier()->set('mail_files_upload_root', sys_get_temp_dir());
        $mail_transaction_id = $this->createMailTransaction();
        $this->assertEquals(
            $this->getArchivePath(sys_get_temp_dir()),
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnDisk($mail_transaction_id)
        );
    }

    public function testGetFilePathOnCloud()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->assertEquals(
            "fn_download_test",
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnCloud($mail_transaction_id)
        );
    }


    public function testGetFilePathOnCloudWithFileOnDiskPath()
    {
        $this->assertEquals(
            $this->fn_download_payload,
            $this->getMailIncludedFilesCloudStorage()->getFilePathOnCloudWithFileOnDiskPath(
                $this->getArchivePath()
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testSetNotAvailable()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->getMailIncludedFilesCloudStorage()->setNotAvailable($mail_transaction_id);
        $info = $this->getSQLQuery()->queryOne("SELECT * FROM mail_transaction WHERE id=?", $mail_transaction_id);
        $this->assertTrue($info['not_available']);
    }

    public function testSetInCloud()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->getMailIncludedFilesCloudStorage()->setInCloud($mail_transaction_id);
        $info = $this->getSQLQuery()->queryOne("SELECT * FROM mail_transaction WHERE id=?", $mail_transaction_id);
        $this->assertTrue($info['is_in_cloud']);
    }

    /**
     * @throws Exception
     */
    public function testFind()
    {
        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        file_put_contents(
            $this->getArchivePath($mail_files_upload_root),
            "test"
        );
        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);
        $this->createMailTransaction();
        $finder = $this->getMailIncludedFilesCloudStorage()->getFinder();
        $this->assertEquals(1, $finder->count());
        $this->assertEquals(
            $this->getArchivePath($mail_files_upload_root),
            array_keys(iterator_to_array($finder->getIterator()))[0]
        );
    }
}
