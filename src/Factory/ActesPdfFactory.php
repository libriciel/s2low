<?php

namespace S2low\Factory;

use S2lowLegacy\Class\actes\ActesPdf;
use S2lowLegacy\Class\actes\ActesPdfLegacy;
use S2lowLegacy\Class\actes\IActesPdf;

class ActesPdfFactory
{
    public function __construct(
        private readonly bool $useLegacyBordereauModel
    ) {
    }

    public function create(): IActesPdf
    {
        return match ($this->useLegacyBordereauModel) {
            true => new ActesPdfLegacy(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"),
            default => new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"),
        };
    }
}
