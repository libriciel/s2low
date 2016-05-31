<?php

class VersionningFactory {
	
	public static function getInstance(){
		
		$version_file = dirname(__FILE__)."/../version.txt";
		$revision_file = dirname(__FILE__)."/../revision.txt";
		$versionning = new Versionning($version_file,$revision_file);
		return $versionning;
	}
}

class Versionning {
	
	private $versionFile ;
	private $revisionFile;
	
	public function __construct($versionFile,$revisionFile){
		$this->versionFile = $versionFile;
		$this->revisionFile = $revisionFile;
	}
	
	public function getRevision(){
		$revisionFileContent = file_get_contents($this->revisionFile);
		foreach(explode("\n",$revisionFileContent) as $line){
			if (preg_match('#^\$Rev: (\d*) \$#',$line,$matches)){
				return $matches[1];
			}
		}
		return false;
	}
	
	public function getDate(){
		$revisionFileContent = file_get_contents($this->revisionFile);
		foreach(explode("\n",$revisionFileContent) as $line){
			if (preg_match('#^\$LastChangedDate: (\d{4}-\d{2}-\d{2}).* \$#',$line,$matches)){
				return $matches[1];
			}
		}
		return false;
	}
	
	public function getVersion(){
		return file_get_contents($this->versionFile);
	}
	
	public function getAllInfo(){
		$result['version'] = $this->getVersion();
		$result['revision'] = $this->getRevision();
		$result['date'] = date("d/m/Y",strtotime($this->getDate()));
		
		$result['version-complete'] =  "Version {$result['version']} - Révision  {$result['revision']} - {$result['date']}" ;
		return $result;
	}
}