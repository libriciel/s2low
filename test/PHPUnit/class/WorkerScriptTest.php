<?php

class WorkerScriptTest extends S2lowTestCase {

	public function testPutJob(){
		/** @var IWorker $IWorker */
		$IWorker = $this->getMockForAbstractClass(IWorker::class);
		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		$this->assertTrue($workerScript->putJob($IWorker,true));
	}

	public function testScript(){
		$IWorker = $this->getMockForAbstractClass(IWorker::class);
		$IWorker->expects($this->any())->method("getAllId")->willReturn([1]);
		/** @var IWorker $IWorker */

		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		$this->assertTrue($workerScript->script($IWorker));
	}


	public function testScriptTerm(){
		$sigTermHandler = $this->getMockBuilder(SigTermHandler::class)->getMock();
		$sigTermHandler->expects($this->any())->method('isSigtermCalled')->willReturn(true);

		$sigTermHandlerFactory = $this->getMockBuilder(SigTermHandlerFactory::class)->getMock();
		$sigTermHandlerFactory->expects($this->any())->method('getNewInstance')->willReturn($sigTermHandler);
		$this->getObjectInstancier()->set(SigTermHandlerFactory::class,$sigTermHandlerFactory);

		$IWorker = $this->getMockForAbstractClass(IWorker::class);
		$IWorker->expects($this->any())->method("getAllId")->willReturn([1]);
		/** @var IWorker $IWorker */

		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		$this->assertTrue($workerScript->script($IWorker));
		$logs_records = $this->getLogRecords();
		$this->assertEquals("SIGTERM reçu",$logs_records[2]['message']);
	}

	public function testScriptFailed(){
		$IWorker = $this->getMockForAbstractClass(IWorker::class);
		$IWorker->expects($this->any())
			->method("getAllId")
			->willThrowException(new Exception("foo"));
		/** @var IWorker $IWorker */

		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		$this->assertFalse($workerScript->script($IWorker));
		$logs_records = $this->getLogRecords();
		$this->assertEquals("Erreur lors de l'execution du script : foo",$logs_records[1]['message']);
	}

	public function testRebuildQueue(){
		$IWorker = $this->getMockForAbstractClass(IWorker::class);
		$IWorker->expects($this->any())->method("getAllId")->willReturn([1]);
		/** @var IWorker $IWorker */

		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		$workerScript->rebuildQueue($IWorker);
		$logs_records = $this->getLogRecords();
		$this->assertEquals("Ajout en file d'attente",$logs_records[1]['message']);
	}

	public function testBeanstalked(){
		$job = $this->getMockBuilder("Pheanstalk\Job")
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue($this->runBeanstalkd($job));
		$logs_records = $this->getLogRecords();
		$this->assertEquals("Travail en cours",$logs_records[1]['message']);
	}

	public function testBeanstalkedFailed(){
		$job = $this->getMockBuilder("Pheanstalk\Job")
			->disableOriginalConstructor()
			->getMock();
		$job->expects($this->any())
			->method('getData')
			->willThrowException(new Exception("foo"));

		$this->assertFalse($this->runBeanstalkd($job));
		$logs_records = $this->getLogRecords();
		$this->assertEquals("foo",$logs_records[1]['message']);
	}

	private function runBeanstalkd($job){
		$queue = $this->getMockBuilder("\Pheanstalk\Pheanstalk")
			->disableOriginalConstructor()
			->getMock();
		$queue->expects($this->at(0))->method('reserve')->willReturn($job);

		$beanstalkdWrapper = $this->getMockBuilder(BeanstalkdWrapper::class)
			->disableOriginalConstructor()
			->getMock();

		$beanstalkdWrapper->expects($this->any())->method('isModeBeanstalked')->willReturn(true);
		$beanstalkdWrapper->expects($this->any())->method('getQueue')->willReturn($queue);

		$this->getObjectInstancier()->set(BeanstalkdWrapper::class,$beanstalkdWrapper);
		$IWorker = $this->getMockForAbstractClass(IWorker::class);
		$IWorker->expects($this->any())->method("getData")->willReturn([1]);
		/** @var IWorker $IWorker */

		$workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
		return $workerScript->script($IWorker);
	}

}

