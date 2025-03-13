<?php

namespace S2lowLegacy\Lib;

class PesAllerData
{
    public function __construct(
        public readonly bool $isPesAcquitRetour,
        public readonly string $cod_col,
        public readonly string $id_post,
        public readonly string $cod_bud
    ) {
    }
}
