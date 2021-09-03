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
     * @throws CloudStorageException | PausingQueueException | UnrecoverableException
     */

	public function storeObject(int $object_id){
		$file_path_on_disk = $this->iCloudStorable->getFilePathOnDisk($object_id);
		$file_path_on_cloud = $this->iCloudStorable->getFilePathOnCloud($object_id);

		if (! $file_path_on_disk){
			$this->logger->error(
				"Unable to store object #{$object_id} in cloud : file_path_on_disk not found !"
			);
			$this->iCloudStorable->setNotAvailable($object_id);
			return false;
		}

		if (! $file_path_on_cloud){
			$error_message = "Unable to store object #{$object_id} in cloud : file_path_on_cloud not found ?!?";
			$this->logger->error($error_message);
			$this->iCloudStorable->setNotAvailable($object_id);
			return false;
		}

		if ( ! file_exists($file_path_on_disk)){
			$this->logger->error(
				"Unable to store object #$object_id in cloud : file $file_path_on_disk did not exist !"
			);
			$this->iCloudStorable->setNotAvailable($object_id);
			return false;
		}

		$this->logger->info(
			sprintf(
				"Storing object #%s - filepath (on disk): %s - filepath (on cloud) : %s",
				$object_id,
				$file_path_on_disk,
				$file_path_on_cloud
			)
		);

		if(!$this->openStackSwiftWrapper->sendFile(
			$this->iCloudStorable->getContainerName(),
			$file_path_on_disk,
			$file_path_on_cloud
		)){
		    return false;
        }


		$this->logger->info("Check file : {$file_path_on_cloud}");
		$check=$this->openStackSwiftWrapper->fileExistsOnCloud(
			$this->iCloudStorable->getContainerName(),
			$file_path_on_cloud
		);
		$this->logger->info("File present ? [{$check}]");
		if (! $check){
			$this->logger->error("File {$file_path_on_disk} not present on cloud after sending ! ");
			return false;
		}

		$this->iCloudStorable->setInCloud($object_id);

		$this->logger->info("Stored object [OK] : $object_id");
		return true;
	}

    /**
     * @deprecated ? on dirait que ca ne sert que dans les tests ?
     * @param int $object_id
     * @return bool
     */
	public function deleteIfIsInCloud(int $object_id){

		$file_path_on_disk = $this->iCloudStorable->getFilePathOnDisk($object_id);
		$file_path_on_cloud = $this->iCloudStorable->getFilePathOnCloud($object_id);

		try {
			if (!$this->openStackSwiftWrapper->fileExistsOnCloud(
				$this->iCloudStorable->getContainerName(),
				$file_path_on_cloud
			)) {
				$this->logger->info(
					"Object #$object_id not existing on cloud : not deleted ($file_path_on_cloud not found)"
				);
				return false;
			}
			$this->logger->info("Deleting object #$object_id : $file_path_on_disk");

			$filesystem = new Filesystem();
			$filesystem->remove($file_path_on_disk);
			return true;
		}catch (Exception $e){
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
	public function deleteFilesOnDisk($no_access_during_nb_days = 9999, $do = true){

		$sigtermHandler = SigTermHandler::getInstance();

		$finder = $this->iCloudStorable->getFinder();

		foreach($finder as $file) {
            echo "-----------------------------\n";
            echo $file->getPath()."\n";

			if ($sigtermHandler->isSigtermCalled()){
				break;
			}
			if ($this->isRecentlyCreated($file,$no_access_during_nb_days)){

				$this->logger->debug("File {$file->getFilename()} too young to die : not deleted");
				continue;
			}
            $filePathOnCloudWithFileOnDiskPath = $this->getFilePathOnCloudWithFileOnDiskPath($file->getPath());

            $this->logger->debug("File path on cloud : " . $filePathOnCloudWithFileOnDiskPath);

            if (! $this->openStackSwiftWrapper->fileExistsOnCloud(
                $this->iCloudStorable->getContainerName(),
                $filePathOnCloudWithFileOnDiskPath
            )){
				$this->logger->info("File {$file->getRealPath()} not existing on cloud : not deleted");
				$object_id = $this->iCloudStorable->getObjectIdByFilePath($file->getRealPath());
				if (! $object_id){
				    $this->logger->notice("Unable to find object id for the file " . $file->getRealPath());
				    continue;
                }
				if (! $this->iCloudStorable->isAvailable($object_id)){
				    $this->iCloudStorable->setAvailable($object_id,true);
				    $this->logger->info("$object_id set to available");
                } else {
				    $this->logger->notice("Object not yet in cloud");
                }
				continue;
			}
			$this->logger->info("Deleting file : {$file->getRealPath()}");
			if ($do) {
			    $this->iCloudStorable->deleteFileOnDisk($file);
			}
		}
	}

	private function isRecentlyCreated(SplFileInfo $file, $no_access_during_nb_days = 9999){
		$last_access_time = $file->getMTime();
		$nb_seconds_without_access = time() - $last_access_time;
		$no_access_during_nb_seconds = $no_access_during_nb_days*86400;
		$this->logger->debug(
			"Nombre de jour depuis la derniere modif : " . round($nb_seconds_without_access/60/60/24)
		);
		return ($nb_seconds_without_access < $no_access_during_nb_seconds);
	}


	public function getPath(int $object_id) : string {

		$file_path_on_disk = $this->iCloudStorable->getFilePathOnDisk($object_id);
		if (! $file_path_on_disk){
			return false;
		}
		if (file_exists($file_path_on_disk)){
			return $file_path_on_disk;
		}

		$file_path_on_cloud = $this->iCloudStorable->getFilePathOnCloud($object_id);

		try {
			$this->logger->info("Retrieve object #$object_id from cloud ($file_path_on_cloud)");

			$result = $this->openStackSwiftWrapper->retrieveFile(
				$this->iCloudStorable->getContainerName(),
				$file_path_on_disk,
				$file_path_on_cloud
			);
		} catch (Exception $e){
			$this->logger->error(
				"Unable to retrieve $file_path_on_cloud to $file_path_on_disk (object #$object_id) from cloud : " . $e->getMessage(),
				$e->getTrace()
			);
			throw new Exception($e);
		}

		return $result;
	}

    /**
     * @param $file
     * @return array|string|string[]|null
     */
    public function getFilePathOnCloudWithFileOnDiskPath($filePath)
    {
        $filePathOnCloudWithFileOnDiskPath = $this->iCloudStorable
            ->getFilePathOnCloudWithFileOnDiskPath($filePath);

        $TempFilePathOnCloudWithFileOnDiskPath = preg_replace(
            "_/import/_",
            "/import//",
            $filePathOnCloudWithFileOnDiskPath
        );

        if ((!$this->openStackSwiftWrapper->fileExistsOnCloud(
                $this->iCloudStorable->getContainerName(),
                $filePathOnCloudWithFileOnDiskPath))
            &&
            ($this->openStackSwiftWrapper->fileExistsOnCloud(
                $this->iCloudStorable->getContainerName(),
                $TempFilePathOnCloudWithFileOnDiskPath))
        ) {
            $filePathOnCloudWithFileOnDiskPath = $TempFilePathOnCloudWithFileOnDiskPath;
        }
        return $filePathOnCloudWithFileOnDiskPath;
    }
}