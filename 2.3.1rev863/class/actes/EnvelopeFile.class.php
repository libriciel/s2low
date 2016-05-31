<?php 

class EnvelopeFile {
	
	private $xsdValidation;
	private $tmpPath;
	private $lastError;
	private $tmpFolder;
	
	
	public function __construct($actesXSDPath,$tmpPath = "/tmp"){
		$this->xsdValidation = new XSDValidation($actesXSDPath);
		$this->tmpPath = $tmpPath;
	}
	
	public function getLastError(){
		return $this->lastError;
	}
	
	public function getInfo($archive_or_directory){
		if (! file_exists($archive_or_directory)){
			$this->lastError = "Le fichier $archive_or_directory n'existe pas";
			return false;
		}
		
		if (! is_readable($archive_or_directory)){
			$this->lastError = "Le fichier $archive_or_directory n'est pas accessible en lecture";
			return false;
		}
		
		if (is_file($archive_or_directory)){
			$archive_name = basename($archive_or_directory);
			$infoArchive = $this->getInfoFromArchiveName($archive_name);
			if ( ! $infoArchive){
				$this->lastError = "L'archive $archive_name ne possède pas un nom correcte";
				return false;
			}
			$archive_or_directory = $this->extractInTmp($archive_or_directory);
		}
		$result = $this->getInfoTEMP($archive_or_directory);
		
		$this->purgeTmpFolder();
		return $result;
	}
	
	private function extractInTmp($archive_file){
		$this->tmpFolder = $this->tmpPath . "/".uniqid();
		mkdir($this->tmpFolder);
		exec("tar xvzf $archive_file --directory {$this->tmpFolder}");
		return $this->tmpFolder;
	}
	
	private function purgeTmpFolder(){		
		if (! $this->tmpFolder){
			return;
		}
		foreach( $this->getFileFromDirectory($this->tmpFolder) as $file){
			unlink($this->tmpFolder."/".$file);
		}
		rmdir($this->tmpFolder);
	}
	
	
	private function getFileFromDirectory($directory){
		$files_in_archive = scandir($directory);
		array_shift($files_in_archive);
		array_shift($files_in_archive);
		return $files_in_archive;
	}
	
	
	private function getInfoTEMP($folder, $archive_name = false){
		
		$messageMetierXML = new MessageMetierXML();
		$actesEnveloppeXML = new ActesEnveloppeXML();
			
		$files_in_archive = $this->getFileFromDirectory($folder);
		
		$result['files'] = $files_in_archive;
		
		$envelope_file = $this->findEnvelope($files_in_archive);
		
		if ( ! $envelope_file){
			$result['error'] = "Il n'y a pas de fichier enveloppe dans les fichiers";
			return $result;
		}
		$result['envelope_file']  = $envelope_file;
	
		$result['info_enveloppe_name'] = $this->getInfoFromEnveloppeName($envelope_file);
		
		if ($archive_name){
			$result['archive_name'] = $archive_name;
			$result['info_archive_name'] = $this->getInfoFromArchiveName($archive_name);
			$result['archive_and_enveloppe_name_coherent'] = $this->enveloppeAndArchiveNameAreCoherent($envelope_file,$archive_name);
		}
			
		$enveloppe_content = file_get_contents($folder."/".$envelope_file);
			
		if (! $this->xsdValidation->validate($enveloppe_content)){
			$result['error'] = "Le contenu de l'enveloppe ne respecte pas le schéma XML ACTES";
			return $result;
		}
	
		$result['enveloppe'] = $actesEnveloppeXML->getInfo($enveloppe_content);
		
		
		if (! $result['enveloppe']){
			$result['error'] = "Impossible de lire le contenu de l'enveloppe";
			return false;
		}
		if ( empty($result['enveloppe']['FormulairesEnvoyes'])){
			$result['error'] = "Il semble que l'enveloppe ne contionnent aucun formulaire";
			return false;
		}
		
		foreach($result['enveloppe']['FormulairesEnvoyes'] as $i => $formulaire){
			
			if (! in_array($formulaire['NomFichier'],$result['files'])){
				$result['error'] = "Le fichier {$formulaire['NomFichier']} n'est pas présent dans l'archive";
				continue;
			}
			$formulaire_content = file_get_contents($folder . "/" . $formulaire['NomFichier']);
			
			if (! $this->xsdValidation->validate($formulaire_content)){
				$result['error'] = "Le fichier {$formulaire['NomFichier']} ne respecte pas le schéma XML ACTES";
				continue;
			}
			
			$result['formulaire'][$i] = $messageMetierXML->getInfo($formulaire_content);	
			if (! empty($result['formulaire'][$i]['error'])){
				$result['error'] = "Un message n'a pas pu être analysé.";
				continue;
			}
			if (isset($result['formulaire'][$i]['Document']['NomFichier'])){
				if (! in_array($result['formulaire'][$i]['Document']['NomFichier'],$result['files'])){
					$result['error'] = "Le fichier {$result['formulaire'][$i]['Document']['NomFichier']} n'est pas présent dans l'archive";
					continue;
				}
			}
			if (isset($result['formulaire'][$i]['Annexes'])){
				foreach($result['formulaire'][$i]['Annexes'] as $k => $t) {
					if (! in_array($result['formulaire'][$i]['Annexes'][$k]['NomFichier'],$result['files'])){
						$result['error'] = "Le fichier {$result['formulaire'][$i]['Annexes'][$k]['NomFichier']} n'est pas présent dans l'archive";
						continue;
					}
				}
			}

		
		}
			
		return $result;
	}
	
	function findEnvelope($files_in_archive){
		foreach($files_in_archive as $file){
			if ($this->isEnvelopeFileName($file)){
				return $file;
			}
		}
		return false;
	}
	
	public function isEnvelopeFileName($file){	
		return preg_match("#^[^-]*-[^-]*-[^-]*-[^-]*-[^-]*-[^-]*\.xml$#",$file);
	}
	
	public function getInfoFromEnveloppeName($enveloppe_name){
		preg_match("#^(ANO_)?(.*)\.xml$#",$enveloppe_name,$ret);
		
		if (count($ret) != 3) {
			return false;
		}
		
		$result['anomalie'] = $ret[1]?true:false;
		
		$result += $this->infoFromName($ret[2]);
		return $result;
	}

	public function getInfoFromArchiveName($archive_name){	
		preg_match("#^([^-]*)-(.*)\.tar\.gz$#",$archive_name,$ret);
		
		if (count($ret) != 3) {
			return false;
		}
		$result['trigramme'] =  $ret[1];
		$result += $this->infoFromName($ret[2]);
		return $result;
	}
	
	private function infoFromName($name){			
		$name_part = explode("-",$name);
		if (count($name_part) != 6){
			return false;
		}
		
		foreach(array('quadrigramme','infoAppli','emmetteur','destinataire','date','numero_enveloppe') as $i => $name_item){
			$result[$name_item] = $name_part[$i];
		}
		
		return $result;
	}
	
	public function enveloppeAndArchiveNameAreCoherent($enveloppeName,$archiveName){		
		preg_match("#^(.*).xml$#",$enveloppeName,$retEnveloppe);
		preg_match("#^[^-]*-(.*).tar.gz$#",$archiveName,$retArchive);
		if (empty($retEnveloppe[1]) || empty($retArchive[1])){
			return false;
		}
		return $retEnveloppe[1] == $retArchive[1];	
	}
	
}