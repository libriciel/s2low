<?php 
class Recuperateur {
	
	private $tableauInput;
	
	public function __construct(array $tableauInput){
		$this->tableauInput = $tableauInput;
	}
	
	public function getInt($name,$default = 0){
        return $this->doSomethingOnValueOrArray('intval', $this->get($name, $default));
	}
	
	public function get($name,$default = false){
		if ( empty($this->tableauInput[$name])) {
			return $default;
		}
		$value = $this->tableauInput[$name];
        return $this->doSomethingOnValueOrArray("trim", $value);
	}

	public function set($key,$value){
	    $this->tableauInput[$key] = $value;
    }

    private function doSomethingOnValueOrArray($something, $valueOrArray)
    {
        if (is_array($valueOrArray)) {
            return array_map($something, $valueOrArray);
        }
        return $something($valueOrArray);
    }
	
}