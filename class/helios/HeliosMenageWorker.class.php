<?php

class HeliosMenageWorker implements IWorker,IWorkerAlwaysLaunch
{

	const QUEUE_NAME = 'helios-menage';
	const NB_DAYS_IN_DISK = 15;

	private $pesAllerStorage;

	public function __construct(
		PesAllerStorage $pesAllerStorage
	)
	{
		$this->pesAllerStorage = $pesAllerStorage;
	}

	public function getQueueName()
	{
		return sprintf("%s-%s", self::QUEUE_NAME, gethostname());
	}

	public function getData($id)
	{
		return $id;
	}

	public function getAllId()
	{
		return [1];
	}

	/**
	 * @param $data
	 * @return void
	 * @throws Exception
	 */
	public function work($data)
	{
		$this->pesAllerStorage->menageLocal(15,true);
	}

	public function getMutexName($data)
	{
		return $this->getQueueName();
	}

	public function isDataValid($data)
	{
		return true;
	}
}