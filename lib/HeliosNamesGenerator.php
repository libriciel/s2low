<?php

namespace S2lowLegacy\Lib;

class HeliosNamesGenerator
{
    public function getP_MSGFromParameters(string $cod_col, string $id_post, string $cod_bud): string
    {
        return 'PES#' . $cod_col . '#' . $id_post . '#' . $cod_bud;
    }

    public function getP_MSGFromPesAllerData(PesAllerData $data): string
    {
        return $this->getP_MSGFromParameters(
            $data->cod_col,
            $data->id_post,
            $data->cod_bud
        );
    }

    public function createCompleteName(string $siren, string $numero): string
    {
        return "PESALR2_{$siren}_" . date('ymd') . "_{$numero}.xml";
    }
}
