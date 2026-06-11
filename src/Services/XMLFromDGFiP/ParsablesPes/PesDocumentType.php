<?php

namespace S2low\Services\XMLFromDGFiP\ParsablesPes;

use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\CodCol;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\NomFich;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\Siret;

enum PesDocumentType : string
{
    case PES_ACQUIT = 'PES_ACQUIT';
    case PES_RETOUR = 'PES_Retour';
    case UNKNOWN_ROOT = 'unknown_root';

    public static function fromOrUnknown(string $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN_ROOT;
    }

    public function getXsdPath(): string
    {
        if ($this === PesDocumentType::PES_RETOUR) {
            return '/PES_V2/RETOUR/Rev0/PES_Retour.xsd';
        } else {
            return '/PES_V2/Rev0/PES_V2_Acquit_Autonome_V2.xsd';
        }
    }

    /**
     * @return array <string, array<string>>
     */
    public function getKeysToExtract(): array
    {
        if ($this === PesDocumentType::PES_RETOUR) {
            return  [Siret::KEY => Siret::PATH];
        } else {
            return [CodCol::KEY => CodCol::PATH, NomFich::KEY => NomFich::PATH];
        }
    }
}
