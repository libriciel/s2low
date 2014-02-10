<?php 

class ActesEnvelope {
	
	private $root_workspace_path;
	
	public function __construct($root_workspace_path){
		$this->root_workspace_path = $root_workspace_path;
	}
	
	public function deleteFiles($envelope_path){
		$dir_to_delete = dirname($this->root_workspace_path . "/"  . $envelope_path);
		escapeshellarg($dir_to_delete);
		if ( ! $dir_to_delete){
			return false;
		}
		`rm -rf $dir_to_delete`;
	}
	
	
	
	
}