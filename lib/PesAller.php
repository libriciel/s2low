<?php

namespace S2lowLegacy\Lib;

use Exception;

class PesAller
{
    public function getP_MSG($pes_aller_path)
    {
        $pes_xml = simplexml_load_file($pes_aller_path, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (is_null($pes_xml) || is_null($pes_xml->EnTetePES) || is_null($pes_xml->EnTetePES->CodCol)) {    //Quickfix php8
            throw new Exception("La balise EnTetePES/CodCol n'est pas présente ou est vide");
        }
        $cod_col = $pes_xml->EnTetePES->CodCol['V'];
        if (! $cod_col) {
            throw new Exception("La balise EnTetePES/CodCol n'est pas présente ou est vide");
        }
        $id_post = $pes_xml->EnTetePES->IdPost['V'];
        if (! $id_post) {
            throw new Exception("La balise EnTetePES/IdPost n'est pas présente ou est vide");
        }
        $cod_bud = $pes_xml->EnTetePES->CodBud['V'];
        if (! $cod_bud) {
            throw new Exception("La balise EnTetePES/CodBud n'est pas présente ou est vide");
        }
        return $this->getP_MSGFromParameters($cod_col, $id_post, $cod_bud);
    }

    /**
     * @param string $cod_col
     * @param string $id_post
     * @param string $cod_bud
     * @return string
     */
    public function getP_MSGFromParameters(string $cod_col, string $id_post, string $cod_bud): string
    {
        return "PES#" . $cod_col . "#" . $id_post . "#" . $cod_bud;
    }
}
