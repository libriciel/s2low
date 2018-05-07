<?php 
class Recuperateur {
	
	private $tableauInput;
	
	public function __construct(array $tableauInput){
		$this->tableauInput = $tableauInput;
	}
	
	public function getInt($name,$default = 0){
		return intval($this->get($name,$default));
	}
	
	public function get($name,$default = false){
		if ( empty($this->tableauInput[$name])) {
			return $default;
		}
		$value = $this->tableauInput[$name];
		return trim($value);
	}

	public function set($key,$value){
	    $this->tableauInput[$key] = $value;
    }
	
}