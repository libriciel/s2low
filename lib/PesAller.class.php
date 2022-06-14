<?php

class PesAller {

	public function getP_MSG($pes_aller_path){
		$pes_xml = simplexml_load_file($pes_aller_path, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (is_null($pes_xml) || is_null($pes_xml->EnTetePES) || is_null($pes_xml->EnTetePES->CodCol)){    //Quickfix php8
            throw new Exception("La balise EnTetePES/CodCol n'est pas présente ou est vide");
        }
		$cod_col = $pes_xml->EnTetePES->CodCol['V'];
		if (! $cod_col){
			throw new Exception("La balise EnTetePES/CodCol n'est pas présente ou est vide");
		}
		$id_post = $pes_xml->EnTetePES->IdPost['V'];
		if (! $id_post){
			throw new Exception("La balise EnTetePES/IdPost n'est pas présente ou est vide");
		}
		$cod_bud = $pes_xml->EnTetePES->CodBud['V'];
		if (! $cod_bud){
			throw new Exception("La balise EnTetePES/CodBud n'est pas présente ou est vide");

		}
		return "PES#" . $cod_col . "#" . $id_post . "#" . $cod_bud;
	}

}