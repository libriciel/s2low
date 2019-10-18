<?php

use Monolog\Logger;

class CloudStorageFactory {

	private $openStackSwiftWrapper;
	private $logger;

	public function __construct(
		OpenStackSwiftWrapper $openStackSwiftWrapper,
		Logger $logger
	)
	{
		$this->openStackSwiftWrapper = $openStackSwiftWrapper;
		$this->logger = $logger;
	}

	public function getInstance(ICloudStorable $ICloudStorable){
		return new CloudStorage(
				$ICloudStorable,
				$this->openStackSwiftWrapper,
				$this->logger
		);
	}

}