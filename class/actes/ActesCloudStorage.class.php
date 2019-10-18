<?php

use \Symfony\Component\Finder\Finder;

class ActesCloudStorage implements ICloudStorable {

	const CONTAINER_NAME = 'acte_envelope';

	private $actesEnvelopeSQL;
	private $actes_files_upload_root;

	public function __construct(
		$actes_files_upload_root,
		ActesEnvelopeSQL $actesEnvelopeSQL
	) {
		$this->actesEnvelopeSQL = $actesEnvelopeSQL;
		$this->actes_files_upload_root = $actes_files_upload_root;
	}

	public function getContainerName(): string
	{
		return self::CONTAINER_NAME;
	}

	public function getAllObjectIdToStore(): array
	{
		return $this->actesEnvelopeSQL->getAllEnvelopepIdToSendInCloud();
	}

	public function getFilePathOnDisk(int $object_id): string
	{
		$envelope_info = $this->actesEnvelopeSQL->getInfo($object_id);
		return $this->actes_files_upload_root."/".$envelope_info['file_path'];
	}

	public function getFilePathOnCloud(int $object_id): string
	{
		$envelope_info = $this->actesEnvelopeSQL->getInfo($object_id);
		return $envelope_info['file_path'];
	}

	public function getFilePathOnCloudWithFileOnDiskPath(string $file_on_disk_path): string
	{
		return $this->actes_files_upload_root."/".$file_on_disk_path;
	}

	public function setNotAvailable(int $object_id): void
	{
		$this->actesEnvelopeSQL->setEnveloppeNotAvailable($object_id);
	}

	public function setInCloud(int $object_id): void
	{
		$this->actesEnvelopeSQL->setTransactionInCloud($object_id);
	}

	public function getFinder(): Finder
	{
		$finder = new Finder();
		$finder->in($this->actes_files_upload_root."/*/*.tar.gz");
		return $finder;
	}
}