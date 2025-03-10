<?php

namespace S2lowLegacy\Lib;

class HeliosNamesGenerator
{
    /**
     * @param string $cod_col
     * @param string $id_post
     * @param string $cod_bud
     * @return string
     */
    public function getP_MSGFromParameters(string $cod_col, string $id_post, string $cod_bud): string
    {
        return 'PES#' . $cod_col . '#' . $id_post . '#' . $cod_bud;
    }

    public function getP_MSGFromPesAllerData(PesAllerData $data): string
    {
        return $this->getP_MSGFromParameters(
            $data->getCodCol(),
            $data->getIdPost(),
            $data->getCodBud()
        );
    }
}
