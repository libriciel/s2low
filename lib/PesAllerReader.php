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

        $id_post = $pes_xml->EnTetePES->IdPost['V'];
        $cod_bud = $pes_xml->EnTetePES->CodBud['V'];

        $expectedLength = 3;
        $nomBalise = 'EnTetePES/CodCol ou EnTetePES/CodColl';
        $this->checkStringLength($cod_col, $expectedLength, $nomBalise);

        $expectedLength = 6;
        $nomBalise = 'EnTetePES/IdPost';
        $this->checkStringLength($id_post, $expectedLength, $nomBalise);

        $expectedLength = 2;
        $nomBalise = 'EnTetePES/CodBud';
        $this->checkStringLength($cod_bud, $expectedLength, $nomBalise);

        return new PesAllerData(
            $isPesAcquitRetour,
            $cod_col,
            $id_post,
            $cod_bud
        );
    }

    /**
     * @param string $element
     * @param int $expectedLength
     * @param string $nomBalise
     * @return void
     * @throws \Exception
     */
    private function checkStringLength(?string $element, int $expectedLength, string $nomBalise): void
    {
        if (! $element) {
            throw new Exception(
                sprintf(
                    'La balise %s n\'est pas présente ou est vide',
                    $nomBalise
                ),
            );
        }

        $strlenCodCol = strlen($element);
        if ($strlenCodCol != $expectedLength) {
            throw new Exception(
                sprintf(
                    'Non-conformité PES aller : la balise %s contient %s caractères (%s attendus).',
                    $nomBalise,
                    $strlenCodCol,
                    $expectedLength
                )
            );
        }
    }
}
