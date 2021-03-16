<?php

class ObjectInstancier {

	private $objects;
		
	public function __construct(){		
		$this->objects = array('ObjectInstancier' => $this);
	}
	
	public function __get($name){
		return $this->get($name);
	}
	
	public function __set($name,$value){
		$this->set($name,$value);
	}

	public function get($name,$dump=false){
	    /*if($dump){
            echo "<p>".var_dump($name)."</p>";
            echo "<p>".var_dump(spl_object_id($this))."</p>";
            echo "<p>".var_dump(array_keys($this->objects))."</p>";
            echo "<p>".var_dump($this->objects[$name])."</p>";
            echo "<p>".get_class($this->objects[$name])."</p>";
            echo "<p>".print_r($this->objects)."</p>";
        }*/
		if (! isset($this->objects[$name])){
			$this->objects[$name] =  $this->newInstance($name);
		}
		return $this->objects[$name];
	}

	public function unset_object($name){
	    echo "<h1>UNSEEEEEEEEEEET</h1>";
		unset($this->objects[$name]);
	}

	public function set($name,$value){
	    echo "<h1>set $name</h1>";
	    var_dump($name);
	    var_dump($value)    ;
	    echo "<br/>";
		$this->objects[$name] = $value;
	}

	private function newInstance($className){
		$reflexionClass = new ReflectionClass($className);
		if (! $reflexionClass->hasMethod('__construct')){
			return $reflexionClass->newInstance();
		}
		$constructor = $reflexionClass->getMethod('__construct');
        $allParameters = $constructor->getParameters();
        $param = $this->bindParameters($className,$allParameters);        
        return $reflexionClass->newInstanceArgs($param);
	}

	private function bindParameters($className,array $allParameters){
		$param = array();
		foreach($allParameters as $parameters){
			/* @var $parameters ReflectionParameter */
        	$param_name = $parameters->getClass() ? $parameters->getClass()->name : $parameters->name;
        	
        	try {
        		$bind_value = $this->$param_name;
        	} catch (Exception $e){

				//throw $e;
        		//On a pas trouvé le paramètre...
        	}
        	
        	if (! isset($bind_value) ) {
        		
        		if ($parameters->isOptional()){
        			return $param;
        		}
        		throw new Exception("Impossible d'instancier $className car le parametre {$parameters->name} est manquant");
        	}
        	$param[] = $bind_value;
        }
        return $param;
	}
}