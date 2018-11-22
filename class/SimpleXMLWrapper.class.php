<?php

class SimpleXMLWrapper {

	private $load_option;

	public function __construct(){
		$this->setLoadOption(LIBXML_PARSEHUGE);
	}

	public function setLoadOption($load_option){
		$this->load_option = $load_option;
	}

    /**
     * @param $file_path
     * @return SimpleXMLElement
     * @throws Exception
     */
	public function loadFile($file_path){
		$save = libxml_use_internal_errors(true);
		$xml = simplexml_load_file($file_path,"SimpleXMLElement",$this->load_option);
		if ($xml === false) {
			$errors = $this->getErrorString();
			libxml_use_internal_errors($save);
			throw new Exception("Le fichier $file_path n'est pas un XML correct : " . $errors);
		}
		libxml_use_internal_errors($save);
		return $xml;
	}

    /**
     * @param $data
     * @return SimpleXMLElement
     * @throws Exception
     */
	public function loadString($data) {
		$save = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($data,"SimpleXMLElement",$this->load_option);
		if ($xml === false) {
			$errors = $this->getErrorString();
			libxml_use_internal_errors($save);
			throw new Exception("XML incorrect : " . $errors);
		}
		libxml_use_internal_errors($save);
		return $xml;
	}

	public function getErrorString() {
		$errors = '';
		foreach (libxml_get_errors() as $error) {
			$errors .= trim($error->message) . '(Lig' . $error->line . ',Col' . $error->column . ")\n";
		}
		return $errors;
	}

}