<?php

namespace S2lowLegacy\Lib;

use Exception;

class PesAllerReader
{
    /**
     * @throws Exception
     */
    public function getPesAllerData($pes_aller_path): PesAllerData
    {
        $pes_xml = simplexml_load_file($pes_aller_path, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (!$pes_xml || empty($pes_xml->EnTetePES)) {
            throw new Exception("La balise EnTetePES n'est pas présente ou est vide");
        }
        $isPesAcquitRetour = $pes_xml->getName() === 'PES_ACQUIT_RETOUR';

        if ($isPesAcquitRetour) {
            $cod_col = $pes_xml->EnTetePES->CodColl['V'];
        } else {
            $cod_col = $pes_xml->EnTetePES->CodCol['V'];
        }

        if (! $cod_col) {
            throw new Exception('La balise EnTetePES/CodCol ou EnTetePES/CodColl n\'est pas présente ou est vide');
        }
        $id_post = $pes_xml->EnTetePES->IdPost['V'];
        if (! $id_post) {
            throw new Exception("La balise EnTetePES/IdPost n'est pas présente ou est vide");
        }
        $cod_bud = $pes_xml->EnTetePES->CodBud['V'];
        if (! $cod_bud) {
            throw new Exception("La balise EnTetePES/CodBud n'est pas présente ou est vide");
        }
        return new PesAllerData(
            $isPesAcquitRetour,
            $cod_col,
            $id_post,
            $cod_bud
        );
    }
}
