<?php
class JSONoutput {
	
	public function displayErrorAndExit($Errormessage){
		$result['status'] = 'error';
		$result['error-message'] = $Errormessage;;
		$this->display($result);
		if (TESTING_ENVIRONNEMENT){
			throw new Exception("Exit !");
		}
		exit;  // @codeCoverageIgnore
	}

	public function displayAndExit($message){
		$result['status'] = 'ok';
		$result['message'] = $message;;
		$this->display($result);
		if (TESTING_ENVIRONNEMENT){
			throw new Exception("Exit !");
		}
		exit;  // @codeCoverageIgnore
	}



	private function normalize($array){
		if (! is_array($array)){
			return utf8_encode($array ??"");
		}
		$result = array();
		foreach ($array as $cle => $value) {
			$result[utf8_encode($cle)] = $this->normalize($value);
		}
		return $result;
	}
	
	public function retrictAndDisplay($data,array $colname_to_display){
		$result = array();
		foreach($data as $line){
			$node = array();
			foreach($colname_to_display as $name){
				$node[$name] = $line[$name];
			}
			$result[] = $node;
		}
		$this->display($result);
	}
	
	public function display(array $array){	
		//header("Content-type: application/json");
		header_wrapper("Content-type: text/plain");
		$array = $this->normalize($array);
		echo json_encode($array);
		
	}	
	
}