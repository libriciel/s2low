<?php 
class DIA_SOAP {
	
	private $apiAction;
	
	public function __construct(DIAAction $apiAction){
		$this->apiAction = $apiAction;
	}
	
	private function getError($Errormessage){
		$result['status'] = 'error';
		$result['error-message'] = $Errormessage;;
		return $result;
	}
	
	public function __call($name,$arguments){
		try {
			$reflexionClass = new ReflectionClass('DIAAction');
			$method = $reflexionClass->getMethod($name);
			$result = $method->invokeArgs($this->apiAction,$arguments);
		} catch (Exception $e){
			return new SoapFault("DIA:$name", utf8_encode($e->getMessage()));
		}
		return utf8_encode_array($result);	
	}
	
	
}