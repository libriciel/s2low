<?php

class WorkerScript {

	const QUEUE_DELAY_RETRY_IN_SECONDS = 60;
	const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

	private $logger;
	private $beanstalkdWrapper;
	private $sigTermHandlerFactory;

	private $min_execution_time_in_seconds;

	/**
	 * @var SigTermHandler
	 */
	private $sigTermHandler;

	public function __construct(
		BeanstalkdWrapper $beanstalkdWrapper,
		S2lowLogger $s2lowLogger,
		SigTermHandlerFactory $sigTermHandlerFactory
	){
		$this->logger = $s2lowLogger;
		$this->beanstalkdWrapper = $beanstalkdWrapper;
		$this->sigTermHandlerFactory = $sigTermHandlerFactory;
		$this->setMinExecutionTimeInSeconds(self::MIN_EXECUTION_TIME_IN_SECONDS);
	}

	public function setMinExecutionTimeInSeconds($min_execution_time_in_seconds){
		$this->min_execution_time_in_seconds=$min_execution_time_in_seconds;
	}

	public function putJob(IWorker $IWorker, $data){
		return $this->beanstalkdWrapper->put($IWorker->getQueueName(),$data);
	}

	public function script(IWorker $IWorker){
		$this->sigTermHandler = $this->sigTermHandlerFactory->getNewInstance();
		if ($this->beanstalkdWrapper->isModeBeanstalked()){
			return $this->beanstalkdWorker($IWorker);
		} else {
			return $this->oldSchoolScript($IWorker);
		}
	}

	public function rebuildQueue(IWorker $IWorker){
		$this->logger->setName($IWorker->getQueueName()."-rebuild-queue");

		$this->beanstalkdWrapper->emptyQueue($IWorker->getQueueName());
		$this->logger->info("Reconstruction de la file ".$IWorker->getQueueName());
		foreach($IWorker->getAllId() as $id){
			$this->putJob($IWorker,$id);
			$this->logger->info("Ajout en file d'attente",[$id]);
		}
		$this->logger->info("Reconstruction de la file ".$IWorker->getQueueName().": OK");
	}

	private function beanstalkdWorker(IWorker $IWorker){
		$queue = $this->beanstalkdWrapper->getQueue($IWorker->getQueueName());
		$this->logger->info("Démarrage en mode beanstalkd");
		while($job = $queue->reserve()){
			if ($this->sigTermHandler->isSigtermCalled()){
				return true;
			}
			$data = "undefined";
			try {
				$data = $job->getData();
				$this->logger->info("Travail en cours",[$data]);
				$IWorker->work($data);
				$queue->delete($job);
			} catch (Exception $e){
				$this->logger->error(
					$e->getMessage(),
					[$data,$e->getTraceAsString()]
				);
				$queue->release(
					$job,
					\Pheanstalk\PheanstalkInterface::DEFAULT_PRIORITY,
					self::QUEUE_DELAY_RETRY_IN_SECONDS
				);
				return false;
			}
			if ($this->sigTermHandler->isSigtermCalled()){
				return true;
			}
		}
		return true;
	}

	private function oldSchoolScript(IWorker $IWorker){
		$start = time();

		$this->logger->info("Démarrage en mode supervisord");

		try {
			$this->checkAll($IWorker);
		} catch (WorkerScriptException $e){
			$this->logger->notice($e->getMessage());
			return true;
		} catch (Exception $e){
			$this->logger->critical(
				"Erreur lors de l'execution du script : " . $e->getMessage(),[$e->getTraceAsString()]
			);
			return false;
		}

		$sleep = $this->min_execution_time_in_seconds - (time() -$start);
		if ($sleep > 0){
			$this->logger->debug("Arret du script $sleep secondes");
			sleep_wrapper($sleep);
		}
		return true;
	}

	/**
	 * @param IWorker $IWorker
	 * @throws WorkerScriptException
	 */
	private function checkAll(IWorker $IWorker){
		$id_list = $IWorker->getAllId();
		$this->logger->info(count($id_list) . " travaux trouvées");

		foreach($id_list as $id){
			if ($this->sigTermHandler->isSigtermCalled()){
				throw new WorkerScriptException("SIGTERM reçu");
			}
			$data = $IWorker->getData($id);
			$IWorker->work($data);
		}
	}
}