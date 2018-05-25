<?php

class BeanstalkdWrapper {

	private $mode_beanstalkd;
	private $beanstalkd_server;
	private $beanstalkd_port;

	private $logger;

	public function __construct($mode_beanstalkd,$beanstalkd_server,$beanstalkd_port,Monolog\Logger $logger) {
		$this->mode_beanstalkd = $mode_beanstalkd;
		$this->beanstalkd_server = $beanstalkd_server;
		$this->beanstalkd_port = $beanstalkd_port;
		$this->logger = $logger;
	}

	public function put($queue_name,$data){
		if (! $this->mode_beanstalkd){
			return true;
		}
		try {
			$queue = new \Pheanstalk\Pheanstalk($this->beanstalkd_server);
			$queue->useTube($queue_name)->put($data);
		} catch (Exception $e){
			$this->logger->error(
				"Unable to send data to queue : " . $e->getMessage(),
				['queue'=>$queue,'data'=>$data]
			);
			return false;
		}
		return true;
	}


}