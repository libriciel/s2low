<?php

namespace S2lowLegacy\Class\mailsec;

use S2lowLegacy\Class\ICloudStorable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MailIncludedFilesCloudStorage implements ICloudStorable
{
    public const CONTAINER_NAME = "mailsec_included_files";

    private $mailTransactionSQL;
    private $mail_files_upload_root;

    public function __construct(
        MailTransactionSQL $mailTransactionSQL,
        $mail_files_upload_root,
        $mail_files_upload_sans_transaction
    ) {
        $this->mailTransactionSQL = $mailTransactionSQL;
        $this->mail_files_upload_root = $mail_files_upload_root;
        $this->mail_files_upload_sans_transaction = $mail_files_upload_sans_transaction;
    }

    public function getContainerName(): string
    {
        return self::CONTAINER_NAME;
    }

    public function getAllObjectIdToStore(): array
    {
        return $this->mailTransactionSQL->getTransactionIdToSendInCloud();
    }

    public function getFilePathOnDisk(int $object_id): string
    {
        $fn_donwload = $this->mailTransactionSQL->getFnDownload($object_id);
        return sprintf("%s/%s/mail.zip", $this->mail_files_upload_root, $fn_donwload);
    }

    public function getFilePathOnCloud(int $object_id): string
    {
        return $this->mailTransactionSQL->getFnDownload($object_id);
    }

    public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
    {
        return basename(dirname($file_on_disk_path));
    }

    public function setNotAvailable(int $object_id): void
    {
        $this->mailTransactionSQL->setNotAvailable($object_id);
    }

    public function setInCloud(int $object_id, bool $inCloud = true): void
    {
        $this->mailTransactionSQL->setInCloud($object_id, $inCloud);
    }

    public function getFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->mail_files_upload_root)->name("mail.zip");
        return $finder;
    }

    public function deleteFileOnDisk(\SplFileInfo $file): void
    {
        $filesystem = new Filesystem();
        $dirname = $file->getPath();
        $filesystem->remove($file->getRealPath());
        if (count(scandir($dirname)) == 2) {
            rmdir($dirname);
        }
    }

    public function getObjectIdByFilePath(string $filepath): int
    {
        return $this->mailTransactionSQL->getIdByFilename(
            $this->getFilePathOnCloudWithFileOnDiskPath($filepath)
        );
    }

    public function setAvailable(int $object_id, bool $available = true): void
    {
        $this->mailTransactionSQL->setAvailable($object_id, $available);
    }

    public function isAvailable(int $object_id): bool
    {
        return $this->mailTransactionSQL->isAvailable($object_id);
    }

    public function isTransactionInCloud(int $object_id)
    {
        return $this->mailTransactionSQL->isInCloud($object_id);
    }

    public function getNoRelatedOjectInDBDirectory()
    {
        return $this->mail_files_upload_sans_transaction;
    }

    public function getRootPath()
    {
        return $this->mail_files_upload_root;
    }
}
