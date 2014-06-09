<?php
class HeliosSignature {
	
	public function getInfoForSignature($xml_file_path){
		$xml = simplexml_load_file($xml_file_path);

		if ($xml->PES_DepenseAller){
			$root = $xml->PES_DepenseAller;
		} else if($xml->PES_RecetteAller) {
			$root = $xml->PES_RecetteAller;
		} else {
			throw new Exception("Le bordereau ne contient ni Depense ni Recette");			
		}
		
		$id = array();
		$hash = array();
		foreach($root->Bordereau as $bordereau){
			//TODO doit-on injecter l'idenfiant du bordereau?
			$id[]=strval($bordereau->BlocBordereau->IdBord['V']);
			$dom = dom_import_simplexml($bordereau);
			$data_to_sign = $dom->C14N(true, false);
			$hash[] = sha1($data_to_sign);
		}
		
		$info = array();
		$info['bordereau_hash'] = implode(",",$hash);
		$info['bordereau_id'] = implode(",",$id);
		
		return $info;
	}
	
	
}