<?php

class ActesNameArchive {

	private $trigramme;
	private $quadrigramme;


	public function __construct($trigramme, $quadrigramme){
		$this->trigramme = $trigramme;
		$this->quadrigramme = $quadrigramme;
	}

	public function verifNameOK($archive_name){
		$archive_name_element = explode("-",$archive_name);
		if (count($archive_name_element) < 2){
			throw new Exception("Le nom de l'archive n'est pas correct");
		}
		if ($archive_name_element[0] != $this->trigramme){
			throw new Exception("Le trigramme trouvé ({$archive_name_element[0]}) ne correspond pas au trigramme attendu ({$this->trigramme})");
		}
		if ($archive_name_element[1] != $this->quadrigramme){
			throw new Exception("Le quadrigramme trouvé ({$archive_name_element[1]}) ne correspond pas au quadrigramme attendu ({$this->quadrigramme})");
		}
		return true;
	}

}