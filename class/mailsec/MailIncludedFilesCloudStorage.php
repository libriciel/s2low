<?php

namespace S2lowLegacy\Class\mailsec;

use S2lowLegacy\Class\ICloudStorable;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MailIncludedFilesCloudStorage implements ICloudStorable
{
    public const CONTAINER_NAME = 'mailsec_included_files';

    private MailTransactionSQL $mailTransactionSQL;
    private string $mail_files_upload_root;
    private string $mail_files_without_transac_dir;

    public function __construct(
        MailTransactionSQL $mailTransactionSQL,
        string $mail_files_upload_root,
        string $mail_files_without_transac_dir
    ) {
        $this->mailTransactionSQL = $mailTransactionSQL;
        $this->mail_files_upload_root = $mail_files_upload_root;
        $this->mail_files_without_transac_dir = $mail_files_without_transac_dir;
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
        return sprintf('%s/%s/mail.zip', $this->mail_files_upload_root, $fn_donwload);
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
        $finder = (new Finder())
            ->in($this->mail_files_upload_root)
            ->name('mail.zip');

        $dirtemp = $this->mail_files_without_transac_dir;

        $relative_without_transac_dir = $this->getPathRelativeToUploadDir($dirtemp);

        if ($relative_without_transac_dir === $this->mail_files_without_transac_dir) {
            // Le répertoire contenant les fichiers sans transaction n'est pas contenu dans
            // le répertoire contenant l'ensemble des fichiers
            return $finder;
        }

        // Si le répertoire contenant les fichiers sans transaction est contenu dans
        // le répertoire contenant l'ensemble des fichiers, on doit l'exclure.
        return $finder->exclude($relative_without_transac_dir);
    }

    public function deleteFileOnDisk(SplFileInfo $file): void
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

    public function isTransactionInCloud(int $object_id): bool
    {
        return $this->mailTransactionSQL->isInCloud($object_id);
    }

    public function getDirectoryForFilesWithoutTransaction(): ?string
    {
        return $this->mail_files_without_transac_dir;
    }

    /**
     * @param string $dirtemp
     * @return string|null
     */
    public function getPathRelativeToUploadDir(string $dirtemp): string|null
    {
        return preg_replace(
            '#^' . preg_quote(realpath($this->mail_files_upload_root) . '/', '#') . '#',
            '',
            realpath($dirtemp)
        );
    }
}
