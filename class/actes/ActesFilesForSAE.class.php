<?php

class ActesFilesForSAE {

	public $actes_filepath;
	public $actes_filename;
	public $signature_filepath;
	public $actes_tamponnees_filepath;
	public $actes_tamponnees_filename;
	public $date_postage;
	public $bordereau_filepath;
	public $annexe = [];
	public $aractes_filepath;
	public $echange_prefecture;
	public $type_pj;


	public function renameSameFilename(){
		$all_filename[$this->actes_filename] = 1;
		foreach($this->annexe as $num_annexe => $annexe){
			$annexe_filename = $this->transformeFilename($annexe['filename']);
			if (isset($all_filename[$annexe_filename])){
				$path_info = pathinfo($annexe['filename']);
				$this->annexe[$num_annexe]['filename'] = sprintf(
					"%s_%d" ,
					$path_info['filename'],
					$all_filename[$annexe_filename]
				);
				if (isset($path_info['extension'])){
					$this->annexe[$num_annexe]['filename'].=".{$path_info['extension']}";
				}
			} else {
				$all_filename[$annexe_filename] = 0;
			}
			$all_filename[$annexe_filename]++;
		}
	}

	/* Pastell supprime les accents des noms de fichiers */
	private function transformeFilename($filename){
		return preg_replace('/[^\x20-\x7E]/','', $filename);
	}

}