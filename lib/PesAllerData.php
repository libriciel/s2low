<?php

namespace S2lowLegacy\Lib;

class PesAllerData
{
    public function __construct(
        private bool $isPesAcquitRetour,
        private string $cod_col,
        private string $id_post,
        private string $cod_bud
    ) {
    }

    /**
     * @return bool
     */
    public function isPesAcquitRetour(): bool
    {
        return $this->isPesAcquitRetour;
    }

    /**
     * @return string
     */
    public function getCodCol(): string
    {
        return $this->cod_col;
    }

    /**
     * @return string
     */
    public function getIdPost(): string
    {
        return $this->id_post;
    }

    /**
     * @return string
     */
    public function getCodBud(): string
    {
        return $this->cod_bud;
    }
}
