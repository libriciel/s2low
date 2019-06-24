<?php 


class TGZExtractor {
	
	private $tmpFolder;
	
	public function __construct($tmpFolder){
		$this->tmpFolder = $tmpFolder;
	}
	
	public function extract($archivePath,$name){
		$command = "tar xvzf $archivePath --directory {$this->tmpFolder} $name";
		$status = exec($command);
		if (! $status){
			$this->lastError = "Impossible d'extraire le fichier $name";
			return false;
		}
		return true;
	}
	
	
}