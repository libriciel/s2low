<?php 

class HeliosFiles {
	
	private $helios_file_upload;
	
	public function __construct($helios_file_upload){
		$this->helios_file_upload = $helios_file_upload;
	}
	
	public function deleteFiles(array $enveloppeInfo){		
		$sending = $this->helios_file_upload;		
		unlink($sending."/{$enveloppeInfo['sha1']}");
		unlink($sending."/{$enveloppeInfo['complete_name']}");		
	}
	
	
}