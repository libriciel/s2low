<?php

class MailsecStoreFilesWorker implements IWorker {

	const QUEUE_NAME = 'mailsec-included-file';

	public function getQueueName(){
		return self::QUEUE_NAME;
	}

	private $cloudStorageFactory;
	private $cloudStorage;

	public function __construct(CloudStorageFactory $cloudStorageFactory) {
		$this->cloudStorageFactory = $cloudStorageFactory;
	}

	public function getData($id){
		return $id;
	}

	/**
	 * @return CloudStorage
	 * @throws UnrecoverableException
	 */
	private function getCloudStorage(){
		if (! $this->cloudStorage){
			$this->cloudStorage = $this->cloudStorageFactory
				->getInstanceByClassName(MailIncludedFilesCloudStorage::class);
		}
		return $this->cloudStorage;
	}

	/**
	 * @return int[]
	 * @throws UnrecoverableException
	 */
	public function getAllId(){
		return $this->getCloudStorage()->getAllObjectIdToStore();
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data){
		$this->getCloudStorage()->storeObject($data);
	}

	public function getMutexName($data) {
		return sprintf("%s-%s",self::QUEUE_NAME,$data);
	}

	public function isDataValid($data) {
		return true;
	}

}