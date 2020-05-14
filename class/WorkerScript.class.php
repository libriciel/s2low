<?php

use \Pheanstalk\PheanstalkInterface;

class WorkerScript {

	const QUEUE_DELAY_RETRY_IN_SECONDS = 60;
	const MIN_EXECUTION_TIME_IN_SECONDS = 10; //uniquement pour le mode non beanstalked

	private $s2lowLogger;
	private $beanstalkdWrapper;
	private $redisMutexWrapper;
	private $sigTermHandlerFactory;

	private $min_execution_time_in_seconds;

	private $objectInstancier;

	/**
	 * @var SigTermHandler
	 */
	private $sigTermHandler;

	public function __construct(
		BeanstalkdWrapper $beanstalkdWrapper,
		S2lowLogger $s2lowLogger,
		SigTermHandlerFactory $sigTermHandlerFactory,
		ObjectInstancier $objectInstancier,
		RedisMutexWrapper $redisMutexWrapper
	){
		$this->s2lowLogger = $s2lowLogger;
		$this->beanstalkdWrapper = $beanstalkdWrapper;
		$this->sigTermHandlerFactory = $sigTermHandlerFactory;
		$this->setMinExecutionTimeInSeconds(self::MIN_EXECUTION_TIME_IN_SECONDS);
		$this->objectInstancier = $objectInstancier;
		$this->redisMutexWrapper = $redisMutexWrapper;
	}


	public function setMinExecutionTimeInSeconds($min_execution_time_in_seconds){
		$this->min_execution_time_in_seconds=$min_execution_time_in_seconds;
	}

	public function putJob(IWorker $IWorker, $data){
		return $this->beanstalkdWrapper->put($IWorker->getQueueName(),$data);
	}

	public function putJobByClassName($workerClassName,$data){
		/** @var IWorker $worker */
		$worker = $this->objectInstancier->get($workerClassName);
		return $this->beanstalkdWrapper->put($worker->getQueueName(),$data);
	}

	public function scriptByClassName($workerClassName, $log_enable_stdout=true, $force_old_school_script = false){
		/** @var IWorker $worker */
		$worker = $this->objectInstancier->get($workerClassName);

		$this->s2lowLogger->setName($worker->getQueueName()."-script");
		$this->s2lowLogger->enableStdOut($log_enable_stdout);
		return $this->script($worker,$force_old_school_script);
	}

	public function script(IWorker $IWorker, $force_old_school_script = false){
		$this->sigTermHandler = $this->sigTermHandlerFactory->getInstance();
		if ($this->beanstalkdWrapper->isModeBeanstalked() && ! $force_old_school_script){
			return $this->beanstalkdWorker($IWorker);
		} else {
			return $this->oldSchoolScript($IWorker);
		}
	}

	public function rebuildQueue(IWorker $IWorker){
		$this->s2lowLogger->setName($IWorker->getQueueName()."-rebuild-queue");

		$this->beanstalkdWrapper->emptyQueue($IWorker->getQueueName());
		$this->s2lowLogger->info("Reconstruction de la file ".$IWorker->getQueueName());
		foreach($IWorker->getAllId() as $id){
			$this->putJob($IWorker,$id);
			$this->s2lowLogger->info("Ajout en file d'attente",[$id]);
		}
		$this->s2lowLogger->info("Reconstruction de la file ".$IWorker->getQueueName().": OK");
	}

	private function beanstalkdWorker(IWorker $IWorker){

		if ($IWorker instanceof IWorkerAlwaysLaunch){
			$this->s2lowLogger->debug("Initialisation avec un job");
			$this->rebuildQueue($IWorker);
		}

		$queue = $this->beanstalkdWrapper->getQueue($IWorker->getQueueName());
		$this->s2lowLogger->info("Démarrage en mode beanstalkd");

		$this->sigTermHandler->setExitOnSignal(true);

		while($job = $queue->reserve()){
			$this->sigTermHandler->setExitOnSignal(false);
			$data = "undefined";
			try {
				$data = $job->getData();
				$this->s2lowLogger->info("Travail en cours",[$data]);
				if ($this->redisMutexWrapper->isRedisMode()){
					$mutex = $this->redisMutexWrapper->getMutex($IWorker->getMutexName($data));
					$mutex->synchronized(function() use ($IWorker,$data){
						$this->syncrhonizedWork($IWorker, $data);
					});
				} else {
					$this->syncrhonizedWork($IWorker,$data);
				}
				$queue->delete($job);

			} catch (Exception $e){
				$this->s2lowLogger->error(
					$e->getMessage(),
					[$data,$e->getTraceAsString()]
				);
				if($e instanceof PausingQueueException){
                    $seconds = $e->getTimeToWait();
                    $this->s2lowLogger->info("Pausing queue for $seconds seconds");
                    sleep($seconds);
                }
				$queue->release(
					$job,
					PheanstalkInterface::DEFAULT_PRIORITY,
					self::QUEUE_DELAY_RETRY_IN_SECONDS
				);
				continue;
			}
			if ($this->sigTermHandler->isSigtermCalled()){
				$this->s2lowLogger->info("Exit on signal (after traitement)" . $this->sigTermHandler->getLastSigNo());
				return true;
			}
			if ($IWorker instanceof IWorkerAlwaysLaunch){
				$this->s2lowLogger->debug("renvoi du job");
				$this->beanstalkdWrapper->put($IWorker->getQueueName(),1,1);
			}
			$this->sigTermHandler->setExitOnSignal(true);
		}
		return true;
	}

	/**
	 * @param IWorker $IWorker
	 * @param $data
	 * @throws RecoverableException
	 */
	private function syncrhonizedWork(IWorker $IWorker, $data){
		$this->s2lowLogger->debug("Entree section critique");
		if ($IWorker->isDataValid($data)){
			$IWorker->work($data);
		} else {
			$this->s2lowLogger->info("Le travail n'est plus à faire, abandon",[$data]);
		}
		$this->s2lowLogger->debug("Sortie section critique");
	}

	private function oldSchoolScript(IWorker $IWorker){
		$start = time();

		$this->s2lowLogger->info("Démarrage en mode supervisord");

		try {
			$this->checkAll($IWorker);
		} catch (WorkerScriptException $e){
			$this->s2lowLogger->notice($e->getMessage());
			return true;
		} catch(PausingQueueException $e){
		    $seconds = $e->getTimeToWait();
            $this->s2lowLogger->info("Pausing queue for $seconds seconds");
            sleep($seconds);
        } catch (Exception $e){
            $message = $e->getMessage();
            $this->s2lowLogger->critical(
				"Erreur lors de l'execution du script : " . $message,[$e->getTraceAsString()]
			);
			return false;
		}

		$sleep = $this->min_execution_time_in_seconds - (time() -$start);
		if ($sleep > 0){
			$this->s2lowLogger->debug("Arret du script $sleep secondes");
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
		$this->s2lowLogger->info(count($id_list) . " travaux trouvées");

		foreach($id_list as $id){
			if ($this->sigTermHandler->isSigtermCalled()){
				throw new WorkerScriptException("SIGTERM reçu");
			}
			$data = $IWorker->getData($id);
			try {
				if ($IWorker->isDataValid($data)){
					$IWorker->work($data);
				} else {
					$this->s2lowLogger->info("Le travail n'est plus à faire, abandon",[$data]);
				}
			} catch (RecoverableException $e){
				/* Nothing to do*/
			}
		}
	}
}