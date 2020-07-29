<?php 

class HeliosFiles {
	
	private $helios_file_upload;

	private $helios_response_root;
	
	public function __construct(
	    $helios_file_upload,
        $helios_response_root
    ){
		$this->helios_file_upload = $helios_file_upload;
		$this->helios_response_root = $helios_response_root;
	}
	
	public function deleteFiles(array $enveloppeInfo){		
		$sending = $this->helios_file_upload;		
		unlink($sending."/{$enveloppeInfo['sha1']}");
		unlink($sending."/{$enveloppeInfo['complete_name']}");
		unlink($this->helios_response_root."/".$enveloppeInfo['acquit_filename']);
	}
	
	
}