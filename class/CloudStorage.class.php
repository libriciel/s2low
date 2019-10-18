<?php

use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;


class CloudStorage {

	private $iCloudStorable;
	private $openStackSwiftWrapper;
	private $logger;

	public function __construct(
		ICloudStorable $iCloudStorable,
		OpenStackSwiftWrapper $openStackSwiftWrapper,
		Logger $logger
	) {
		$this->iCloudStorable = $iCloudStorable;
		$this->openStackSwiftWrapper = $openStackSwiftWrapper;
		$this->logger = $logger;
	}

	public function getAllObjectIdToStore(){
		return $this->iCloudStorable->getAllObjectIdToStore();
	}

	/**
	 * @param int $object_id
	 * @return bool
	 * @throws Exception
	 */
	public function storeObject(int $object_id){
		$file_path_on_disk = $this->iCloudStorable->getFilePathOnDisk($object_id);
		$file_path_on_cloud = $this->iCloudStorable->getFilePathOnCloud($object_id);

		if (! $file_path_on_disk){
			$this->logger->error(
				"Unable to store object #{$object_id} in cloud : file_path_on_disk not found ! "
			);
			$this->iCloudStorable->setNotAvailable($object_id);
			return false;
		}

		if (! $file_path_on_cloud){
			$error_message = "Unable to store object #{$object_id} in cloud : file_path_on_cloud not found ?!? ";
			$this->logger->alert($error_message);
			throw new CloudStorageException($error_message);
		}

		if ( ! file_exists($file_path_on_disk)){
			$this->logger->error(
				"Unable to store #$object_id in cloud : file $file_path_on_disk did not exist ! "
			);
			$this->iCloudStorable->setNotAvailable($object_id);
			return false;
		}

		$this->logger->info(
			"Storing object #$object_id - filepath (on disk): $file_path_on_disk - filepath (on cloud) : $file_path_on_cloud"
		);


		$this->openStackSwiftWrapper->sendFile(
			$this->iCloudStorable->getContainerName(),
			$file_path_on_disk,
			$file_path_on_cloud
		);

		$this->iCloudStorable->setInCloud($object_id);

		$this->logger->info("Stored object [OK] : $object_id");
		return true;
	}

	public function deleteIfIsInCloud(int $object_id){

		$file_path_on_disk = $this->iCloudStorable->getFilePathOnDisk($object_id);
		$file_path_on_cloud = $this->iCloudStorable->getFilePathOnCloud($object_id);

		try {
			if (!$this->openStackSwiftWrapper->fileExistsOnCloud(
				$this->iCloudStorable->getContainerName(),
				$file_path_on_cloud
			)) {
				$this->logger->info("Object #$object_id not existing on cloud : not deleted ($file_path_on_cloud not found)");
				return false;
			}
			$this->logger->info("Deleting object #$object_id : $file_path_on_disk");

			$filesystem = new Filesystem();
			$filesystem->remove($file_path_on_disk);
			return true;
		}catch (Exception $e){
			$this->logger->alert(
				"Problème lors de la supression de l'objet #$object_id $file_path_on_disk : " . $e->getMessage()
			);
			return false;
		}
	}

	/**
	 * @param int $no_access_during_nb_days
	 * @param bool $do
	 */
	public function deleteFilesOnDisk($no_access_during_nb_days = 9999, $do = true){

		$sigtermHandler = SigTermHandler::getInstance();

		$finder = $this->iCloudStorable->getFinder();

		foreach($finder as $file) {
			if ($sigtermHandler->isSigtermCalled()){
				break;
			}
			if ($this->isRecentlyCreated($file,$no_access_during_nb_days)){

				$this->logger->debug("File {$file->getFilename()} too young to die : not deleted");
				continue;
			}
			if (! $this->openStackSwiftWrapper->fileExistsOnCloud(
				$this->iCloudStorable->getContainerName(),
				$this->iCloudStorable->getFilePathOnCloudWithFileOnDiskPath($file->getPath())
			)){
				$this->logger->info("File {$file->getFilename()} not existing on cloud : not deleted");
				continue;
			}
			$this->logger->info("Deleting file : {$file->getPath()}");
			if ($do) {
				$filesystem = new Filesystem();
				$filesystem->remove($file->getPath());
			}
		}
	}

	private function isRecentlyCreated(SplFileInfo $file, $no_access_during_nb_days = 9999){
		$last_access_time = $file->getMTime();
		$nb_seconds_without_access = time() - $last_access_time;
		$no_access_during_nb_seconds = $no_access_during_nb_days*86400;
		$this->logger->debug("Nombre de jour depuis la derniere modif : " . round($nb_seconds_without_access/60/60/24));
		return ($nb_seconds_without_access < $no_access_during_nb_seconds);
	}

}