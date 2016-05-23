<?php

class HeliosPESValidation {
	
	const RELATIVE_PATH_TO_PES_ALLER_XSD = '/PES_V2/Rev0/PES_Aller.xsd';

	private $helios_xsd_path;
	private $last_error;
	
	public function __construct($helios_xsd_path){
		$this->helios_xsd_path = $helios_xsd_path;
		$this->last_error = array();
	}

	public function getLastError(){
		return $this->last_error;
	}
	
	public function validate($pes_content){
		$old_libxml_user_internal_errors = libxml_use_internal_errors(true);
		$dom = new DomDocument();
		libxml_clear_errors();
		$dom->loadXML($pes_content,LIBXML_PARSEHUGE);
		$result = $dom->schemaValidate($this->helios_xsd_path.self::RELATIVE_PATH_TO_PES_ALLER_XSD) ;
		if (! $result){
			$this->last_error = libxml_get_errors();
		}
		libxml_clear_errors();
		libxml_use_internal_errors($old_libxml_user_internal_errors);
		return $result;
	}
}