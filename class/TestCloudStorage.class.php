<?php

use \Symfony\Component\Finder\Finder;

class TestCloudStorage  implements ICloudStorable {

    const CONTAINER_NAME = "helios_pes_retour";

    public function __construct() {
    }

    public function getContainerName(): string
    {
        return self::CONTAINER_NAME;
    }

    public function getAllObjectIdToStore(): array
    {
        return [];
    }

    public function getFilePathOnDisk(int $object_id): string
    {
        return "/var/www/s2low/test/PHPUnit/class/ActesImapRetrieveTest.php";
    }

    public function getFilePathOnCloud(int $object_id): string
    {
        return "ActesImapRetrieveTest.php";
    }

    public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
    {
        return basename($file_on_disk_path);
    }

    public function setNotAvailable(int $object_id): void
    {
    }

    public function setInCloud(int $object_id): void
    {
    }

    public function getFinder(): Finder
    {
    }
}