<?php

class S2lowLogger {

	private $logger;

	public function __construct(Monolog\Logger $logger) {
		$this->logger = $logger;
	}

	public function debug($message,array $context=[]){
		$this->getLoggerWithName()->debug($message,$context);
	}

	public function info($message,array $context=[]){
		$this->getLoggerWithName()->info($message,$context);
	}

	public function notice($message,array $context=[]){
		$this->getLoggerWithName()->notice($message,$context);
	}

	public function warning($message,array $context=[]){
		$this->getLoggerWithName()->warning($message,$context);
	}

	public function error($message,array $context=[]){
		$this->getLoggerWithName()->error($message,$context);
	}

	public function alert($message,array $context=[]){
		$this->getLoggerWithName()->alert($message,$context);
	}

	public function critical($message,array $context=[]){
		$this->getLoggerWithName()->critical($message,$context);
	}

	public function emergency($message,array $context=[]){
		$this->getLoggerWithName()->emergency($message,$context);
	}

	public function enableStdOut(){
		try {
			$handler = new  Monolog\Handler\StreamHandler('php://stdout');
		} catch (Exception $e){
			$message =  "Impossible de créer un streamHandler sur sdtout : " . $e->getMessage();
			echo $message;
			$this->critical($message,[$e]);
		}
		$this->logger->pushHandler($handler);
	}

	private function getLoggerWithName(){
		$trace = debug_backtrace();
		if (empty($trace[2]['class'])){
			$className = basename($trace[1]['file']);
		} else {
			$className = $trace[2]['class'];
		}
		return $this->logger->withName($className);
	}
}