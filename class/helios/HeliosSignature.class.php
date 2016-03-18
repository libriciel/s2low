<?php
class HeliosSignature {
	
	private $xml_starlet_path;
	
	public function __construct($xml_starlet_path = false){
		$this->xml_starlet_path = $xml_starlet_path?:XML_STARLET_PATH;
	}
	
	private function checkRecetteOrDepense($xml){
		if ($xml->PES_DepenseAller){
			return;
		}
		if($xml->PES_RecetteAller) {
			return;
		}
		throw new Exception("Le bordereau ne contient ni Depense ni Recette");
	}
	
	private function hasIdOnAllBordereau($xml){
		foreach(array('PES_DepenseAller','PES_RecetteAller') as $tag){
			if (! $xml->$tag){
				continue;
			}
			foreach($xml->$tag->Bordereau as $bordereau){
				if (empty($bordereau['Id'])){
					return false;
				}
			}
		}
		return true;
	}
	
	public function getSha1($xml_content){
		$tmp_file = tempnam("/tmp/", "s2low_xml_");
		file_put_contents($tmp_file, $xml_content);
	
		if (! is_executable($this->xml_starlet_path)){
			throw new Exception("Impossible d'executer le programme xmlstarlet ({$this->xml_starlet_path})");
		}
	
		$c14n_file = tempnam("/tmp/", "s2low_xml_c14n_");
	
		$command = "{$this->xml_starlet_path} c14n --exc-without-comments {$tmp_file} > {$c14n_file}";
		`$command`;
	
		if (! file_exists($c14n_file)){
			throw new Exception("Impossible de créer le fichier XML canonique $c14n_file");
		}
	
		$result = sha1_file($c14n_file);
	
		return $result;
	}
	
	public function getInfoForSignature($xml_file_path){
		$xml = simplexml_load_file($xml_file_path, 'SimpleXMLElement', LIBXML_PARSEHUGE);

		$this->checkRecetteOrDepense($xml);

		$id = array();
		$hash = array();        
        
		if( $this->hasIdOnAllBordereau($xml) ){
			foreach(array('PES_DepenseAller','PES_RecetteAller') as $tag){
				if (! $xml->$tag){
					continue;
				}
	            foreach($xml->$tag->Bordereau as $bordereau){
	            	$id[]= strval($bordereau['Id']);
	            	$hash[] = $this->getSha1($bordereau->asXML());
	            }
			}
			$isBordereau = true;
        } else if( isset( $xml['Id'] ) && !empty($xml['Id'] ) ) {
        		$id[]  = strval($xml['Id']);
        		$hash[] = $this->getSha1($xml->asXML());
        		$isBordereau = false;
        } else {
			throw new Exception("Le bordereau du fichier PES ne contient pas d'identifiant valide, ni la balise PESAller : signature impossible");
		}
        		
		$info = array();
		$info['bordereau_hash'] = implode(",",$hash);
		$info['bordereau_id'] = implode(",",$id);
		$info['isbordereau'] = $isBordereau;
		
		return $info;
	}

	public function injectSignature($original_file_path,$signature, $isBordereau){
		
		$all_signature = explode(",",$signature);

		$domDocument = new DOMDocument();
		$domDocument->load($original_file_path,LIBXML_PARSEHUGE);
	
		if( $isBordereau ) {
			$all_bordereau = $domDocument->getElementsByTagName('Bordereau');

			foreach($all_signature as $num_bordereau => $signature) {
				$signature_1 = base64_decode($signature);
				$signatureDOM = new DOMDocument();
				$signatureDOM->loadXML($signature_1);
				$signature = $signatureDOM->firstChild->firstChild;
				$cloned = $signature->cloneNode(TRUE);
				
				$bordereauNode = $all_bordereau->item($num_bordereau);

				$bordereauNode->appendChild($domDocument->importNode($cloned,true));
			}
		}
		else {
			$signature_1 = base64_decode($signature);
			$signatureDOM = new DOMDocument();
			$signatureDOM->loadXML($signature_1);
            $signature = $signatureDOM->firstChild->firstChild;
			
            $rootNode = $domDocument->documentElement;
            $rootNode->appendChild($domDocument->importNode($signature,true));
		}

		return $domDocument->saveXml();		
	}
	
}