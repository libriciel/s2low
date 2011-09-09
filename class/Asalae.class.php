<?php

class Asalae {
	
	private $lastError;
	
	private $WDSL;
	private $login;
	private $password;
	private $identifiantVersant;
	private $originatingAgency;
	private $identifiantArchive;
	private $numeroAgrement;
	
	public function __construct(array $authorityInfo){
		$this->WSDL = $authorityInfo['sae_wsdl'];
		$this->login = $authorityInfo['sae_login'];
		$this->password = $authorityInfo['sae_password'];
		$this->numeroAgrement = $authorityInfo['sae_numero_aggrement'];
	}

	public function getLastError(){
		return $this->lastError;
	}
	
	public function sendArchive($bordereauSEDA,$archivePath){
		$seda = base64_encode($bordereauSEDA);
		if (! $seda){
			return false;
		}
		$client = new SoapClient($this->WSDL);

		$document_content  = base64_encode(file_get_contents($archivePath));
		
		$retour  = $client->__soapCall("wsDepot", array("bordereau.xml", $seda,basename($archivePath), $document_content, "TARGZ",$this->login,$this->password));
		if ($retour == 0){
			return true;
		}
		
		$this->lastError = "Erreur lors du dépot : le service d'archive a retourné :  $retour";
		return false;
	}
	
	
	public function getErrorString($number){
		$error = array("connexion réussie","identifiant de connexion inconnu","mot de passe incorrect","connecteur non actif");
		if (empty($error[$number])){
			return "erreur As@lae inconnu ($number)";
		}
		return $error[$number];
	}

}
