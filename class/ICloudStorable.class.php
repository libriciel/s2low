<?php

use \Symfony\Component\Finder\Finder;

interface ICloudStorable {


	public function getContainerName() : string;

	public function getAllObjectIdToStore() : array ;

	public function getFilePathOnDisk(int $object_id) : string;

	public function getFilePathOnCloud(int $object_id) : string;

	public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path) : string;

	public function setNotAvailable(int $object_id) : void;

	public function setInCloud(int $object_id) : void;

	public function getFinder() : Finder;

    public function deleteFileOnDisk(SplFileInfo $file): void;

    public function getObjectIdByFilePath(string $filepath): int;

    public function setAvailable(int $object_id, bool $available) : void;

    public function isAvailable(int $object_id): bool;

}