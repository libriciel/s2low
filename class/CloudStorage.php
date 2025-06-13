<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Exception;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\UnrecoverableException;
use SplFileInfo;
use UnexpectedValueException;

/**
 *
 */
class CloudStorage
{
    private ICloudStorable $cloudStorable;
    private OpenStackSwiftWrapper $openStackSwiftWrapper;
    private LoggerInterface $logger;
    private bool $openstack_enable;

    public function __construct(
        ICloudStorable $iCloudStorable,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        LoggerInterface $logger,
        bool $openstack_enable
    ) {
        $this->cloudStorable = $iCloudStorable;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
        $this->openstack_enable = $openstack_enable;
    }

    /**
     * @return array
     */
    public function getAllObjectIdToStore(): array
    {
        return $this->cloudStorable->getAllObjectIdToStore();
    }

    /**
     * @param int $object_id
     * @return bool
     * @throws CloudStorageException | PausingQueueException | UnrecoverableException
     */

    public function storeObject(int $object_id): bool
    {
        $file_path_on_disk = $this->cloudStorable->getFilePathOnDisk($object_id);
        $file_path_on_cloud = $this->cloudStorable->getFilePathOnCloud($object_id);
        if (! $file_path_on_disk) {
            $this->logger->error(
                "Unable to store object #$object_id in cloud : file_path_on_disk not found !"
            );
            $this->cloudStorable->setNotAvailable($object_id);
            return false;
        }

        if (! $file_path_on_cloud) {
            $error_message = "Unable to store object #$object_id in cloud : file_path_on_cloud not found ?!?";
            $this->logger->error($error_message);
            $this->cloudStorable->setNotAvailable($object_id);
            return false;
        }
        if (! file_exists($file_path_on_disk)) {
            $this->logger->error(
                "Unable to store object #$object_id in cloud : file $file_path_on_disk did not exist !"
            );
            $this->cloudStorable->setNotAvailable($object_id);
            return false;
        }
        $this->logger->info(
            sprintf(
                'Storing object #%s - filepath (on disk): %s - filepath (on cloud) : %s',
                $object_id,
                $file_path_on_disk,
                $file_path_on_cloud
            )
        );

        if (
            !$this->openStackSwiftWrapper->sendFile(
                $this->cloudStorable->getContainerName(),
                $file_path_on_disk,
                $file_path_on_cloud
            )
        ) {
            return false;
        }


        $this->logger->info("Check file : $file_path_on_cloud");
        $check = $this->openStackSwiftWrapper->fileExistsOnCloud(
            $this->cloudStorable->getContainerName(),
            $file_path_on_cloud
        );
        $this->logger->info("File present ? [$check]");
        if (! $check) {
            $this->logger->error("File $file_path_on_disk not present on cloud after sending ! ");
            return false;
        }

        $this->cloudStorable->setInCloud($object_id);

        $this->logger->info("Stored object [OK] : $object_id");
        return true;
    }

    /**
     * @param int $object_id
     * @return bool
     */
    public function deleteIfIsInCloud(int $object_id): bool
    {

        $file_path_on_disk = $this->cloudStorable->getFilePathOnDisk($object_id);
        $file_path_on_cloud = $this->cloudStorable->getFilePathOnCloud($object_id);

        try {
            if (
                !$this->openStackSwiftWrapper->fileExistsOnCloud(
                    $this->cloudStorable->getContainerName(),
                    $file_path_on_cloud
                )
            ) {
                $this->logger->info(
                    "Object #$object_id not existing on cloud : not deleted ($file_path_on_cloud not found)"
                );
                return false;
            }
            $this->logger->info("Deleting object #$object_id : $file_path_on_disk");

            $filesystem = new Filesystem();
            $filesystem->remove($file_path_on_disk);
            return true;
        } catch (Exception $e) {
            $this->logger->alert(
                sprintf(
                    "Problème lors de la supression de l'objet #%s %s : %s",
                    $object_id,
                    $file_path_on_disk,
                    $e->getMessage()
                )
            );
            return false;
        }
    }

    /**
     * @param int $no_access_during_nb_days
     * @param bool $do
     */
    public function deleteFilesOnDisk(int $no_access_during_nb_days = 9999, bool $do = true): void
    {

        $sigtermHandler = SigTermHandler::getInstance();

        $finder = $this->cloudStorable->getFinder();

        foreach ($finder as $file) {
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
            if ($this->isRecentlyCreated($file, $no_access_during_nb_days)) {
                $this->logger->debug("File {$file->getFilename()} too young to die : not deleted");
                continue;
            }
            $filePathOnCloudWithFileOnDiskPath = $this->getFilePathOnCloudWithFileOnDiskPath($file->getRealPath());
            $this->logger->debug('File path on cloud : ' . $filePathOnCloudWithFileOnDiskPath);

            if ($filePathOnCloudWithFileOnDiskPath === '') { # fileExistsOnCloud retourne true à un argument vide ...
                $this->logger->debug(
                    "File {$file->getFilename()} a un nom sur cloud vide ($filePathOnCloudWithFileOnDiskPath)"
                );
                continue;
            }
            if (
                ! $this->openStackSwiftWrapper->fileExistsOnCloud(
                    $this->cloudStorable->getContainerName(),
                    $filePathOnCloudWithFileOnDiskPath
                )
            ) {
                $this->logger->info("File {$file->getRealPath()} not existing on cloud : not deleted");
                $this->handlerOlderFileNotInCloud($file);
                continue;
            }
            $this->logger->info("Deleting file : {$file->getRealPath()}");
            if ($do) {
                $this->cloudStorable->deleteFileOnDisk($file);
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @param int $no_access_during_nb_days
     * @return bool
     */
    private function isRecentlyCreated(SplFileInfo $file, int $no_access_during_nb_days = 9999): bool
    {
        $last_access_time = $file->getMTime();
        $nb_seconds_without_access = time() - $last_access_time;
        $no_access_during_nb_seconds = $no_access_during_nb_days * 86400;
        $this->logger->debug(
            'Nombre de jour depuis la derniere modif : ' . round($nb_seconds_without_access / 60 / 60 / 24)
        );
        return ($nb_seconds_without_access < $no_access_during_nb_seconds);
    }


    /**
     * @param int $object_id
     * @return string|bool
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     * @throws \Exception
     */
    public function getPath(int $object_id): string | bool
    {
        $file_path_on_disk = $this->cloudStorable->getFilePathOnDisk($object_id);
        if (! $file_path_on_disk) {
            return false;
        }
        if (file_exists($file_path_on_disk)) {
            return $file_path_on_disk;
        }

        if (! $this->openstack_enable) {
            throw new Exception("Unable to retrieve $file_path_on_disk and no cloud storage enabled");
        }

        $file_path_on_cloud = $this->cloudStorable->getFilePathOnCloud($object_id);

        $this->retrieveFromCloud($object_id, $file_path_on_cloud, $file_path_on_disk);

        return $file_path_on_disk;
    }

    /**
     * @param int $object_id
     * @return array|bool
     * @throws \Exception
     */
    public function getSize(int $object_id): array|bool
    {

        $file_path_on_disk = $this->cloudStorable->getFilePathOnDisk($object_id);
        if (! $file_path_on_disk) {
            return false;
        }
        if (file_exists($file_path_on_disk)) {
            return [ filesize($file_path_on_disk), sha1_file($file_path_on_disk) ];
        }

        throw new Exception('[getSize] Cloud not yet implemented !!');
        //TODO vérifier l'implémentation depuis le cloud pour les plateformes Adullact et JVS
        //TODO : permettre de récupérer aussi le sha1 !
        /*$file_path_on_cloud = $this->iCloudStorable->getFilePathOnCloud($object_id);

        try {
            $this->logger->info("Retrieve object #$object_id from cloud ($file_path_on_cloud)");

            $result = $this->openStackSwiftWrapper->getFileSize(
                $this->iCloudStorable->getContainerName(),
                $file_path_on_disk,
                $file_path_on_cloud
            );
        } catch (Exception $e) {
            $this->logger->error(
                "Unable to retrieve $file_path_on_cloud to $file_path_on_disk (object #$object_id) from cloud : " .
        $e->getMessage(),
                $e->getTrace()
            );
            throw $e;
        }

        return $result;*/
    }

    /**
     * @param string $filePath
     * @return string
     */
    public function getFilePathOnCloudWithFileOnDiskPath(string $filePath): string
    {
        $filePathOnCloudWithFileOnDiskPath = $this->cloudStorable
            ->getFilePathOnCloudWithFileOnDiskPath($filePath);

        $TempFilePathOnCloudWithFileOnDiskPath = str_replace(
            '/import/',
            '/import//',
            $filePathOnCloudWithFileOnDiskPath
        );

        if (
            (!$this->openStackSwiftWrapper->fileExistsOnCloud(
                $this->cloudStorable->getContainerName(),
                $filePathOnCloudWithFileOnDiskPath
            ))
            &&
            ($this->openStackSwiftWrapper->fileExistsOnCloud(
                $this->cloudStorable->getContainerName(),
                $TempFilePathOnCloudWithFileOnDiskPath
            ))
        ) {
            $filePathOnCloudWithFileOnDiskPath = $TempFilePathOnCloudWithFileOnDiskPath;
        }
        return $filePathOnCloudWithFileOnDiskPath;
    }

    /**
     * @param mixed $file
     * @throws \Exception
     */
    protected function handlerOlderFileNotInCloud(SplFileInfo $file): void
    {
        $object_id = $this->cloudStorable->getObjectIdByFilePath($file->getRealPath());
        if (!$object_id) {
            $this->logger->notice('Unable to find object id for the file ' . $file->getRealPath());
            $this->moveToOrphelinsDirectory($file);
            return;
        }
        if (!$this->cloudStorable->isAvailable($object_id)) {
            $this->cloudStorable->setAvailable($object_id, true);
            $this->logger->info("$object_id set to available");
        }
        if ($this->cloudStorable->isTransactionInCloud($object_id)) {
            $this->logger->info("$file [transaction $object_id] passé à is_in_cloud = false");
            $this->cloudStorable->setInCloud($object_id, false);
        }
    }

    /**
     * @param int $object_id
     * @param string $file_path_on_cloud
     * @param string $file_path_on_disk
     * @return void
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException|\S2lowLegacy\Class\CloudStorageException
     */
    private function retrieveFromCloud(int $object_id, string $file_path_on_cloud, string $file_path_on_disk): void
    {
        try {
            $this->logger->info("Retrieve object #$object_id from cloud ($file_path_on_cloud)");

            $this->openStackSwiftWrapper->retrieveFile(
                $this->cloudStorable->getContainerName(),
                $file_path_on_disk,
                $file_path_on_cloud
            );
        } catch (Exception $e) {
            $this->logger->error(
                "Unable to retrieve $file_path_on_cloud to $file_path_on_disk (object #$object_id) from cloud : " . $e->getMessage(),
                $e->getTrace()
            );
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function moveToOrphelinsDirectory(SplFileInfo $file): void
    {
        if ($this->cloudStorable->getDirectoryForFilesWithoutTransaction() === null) {
            $this->logger->info(
                'File ' . $file->getRealPath() . ' : destination directory ' . $this->cloudStorable->getDirectoryForFilesWithoutTransaction() . ' not found'
            );
            return;
        }
        $shortDestination = $this->cloudStorable->getDesiredPathInDirectoryForFilesWithoutTransaction($file);
        if (
            !$this->rename(
                $file,
                $shortDestination,
                $this->cloudStorable->getDirectoryForFilesWithoutTransaction()
            )
        ) {
            $this->logger->info("File $file : rename KO");
            return;
        }
        $this->logger->info(
            "rename done to $shortDestination in " . $this->cloudStorable->getDirectoryForFilesWithoutTransaction()
        );
    }

    /**
     * @param \SplFileInfo $file
     * @param string $destination
     * @return bool
     */
    private function rename(SplFileInfo $file, string $relative_destination, string $directory): bool
    {
        $destination = $directory . '/' . $relative_destination;
        if (file_exists($destination) && $this->filesAreSame($destination, $file)) {
            unlink($file->getRealPath());
            return true;
        }
        if (file_exists($destination)) {
            throw new Exception("Le fichier $destination existe déjà");
        }
        $sub_directories = explode('/', $relative_destination);
        if (count($sub_directories) > 2) {
            throw new UnexpectedValueException("cas non implémenté (trop de sous-répertoires dans $relative_destination) ");
        }
        if (count($sub_directories) === 2) {
            mkdir($directory . '/' . $sub_directories[0], 0700, true);
        }
        return rename(
            $file->getRealPath(),
            $destination
        );
    }

    private function filesAreSame(string $destination, SplFileInfo $file): bool
    {
        if (filesize($destination) !== filesize($file->getRealPath())) {
            return false;
        }
        return sha1_file($destination) === sha1_file($file->getRealPath());
    }
}
