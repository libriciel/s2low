<?php
class SEDATest {
	
	private $lastError;
	
	public function getTransactionTest(){
		return array(	"nature_descr" => "test",
						"decision_date"=>date("Y-m-d H:i:s"),
						"subject"=>"test",
						"nature_code" => "1",
						"classification"=>"1.1",
						"unique_id" => "111TEST",
						
		);
		
	}
	
	public function validateBordereau($bordereau){
		
		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadXML($bordereau);
		
		$err=  $dom->schemaValidate(__DIR__."/../xsd/seda/archives_echanges_v0-2_archivetransfer.xsd");
		$this->lastError = libxml_get_errors();
		return $err;
	}
	
	public function getLastError(){
		$msg = "<ul>";
		foreach($this->lastError as $err){
			$msg .= "<li>[Erreur #{$err->code}] ".$err->message."</li>";
		}
		return $msg."</ul>";
	}
	
}