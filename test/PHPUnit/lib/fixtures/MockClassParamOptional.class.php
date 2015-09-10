<?php
class MockClassParamOptional {
	
	private $param;
	
	public function __construct($param = 12){
		$this->param = $param;
	}
	
}