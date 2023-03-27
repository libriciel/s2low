<?php

namespace S2lowLegacy\Class\helios;

class HeliosFilesNames
{
    private string $pes_aller_filename;

    private string $pes_aquit_completename;

    private string $pes_aquit_filename;

    /**
     * @return string
     */
    public function getPesAllerFilename(): string
    {
        return $this->pes_aller_filename;
    }

    /**
     * @return string
     */
    public function getPesAquitCompletename(): string
    {
        return $this->pes_aquit_completename;
    }

    /**
     * @return string
     */
    public function getPesAquitFilename(): string
    {
        return $this->pes_aquit_filename;
    }

    public function getFileNames(): array
    {
        return [
            "pes acquit" => $this->getPesAquitFilename(),
            "pes aller" => $this->getPesAllerFilename(),
            "pes acquit nom complet" => $this->getPesAquitCompletename()
        ];
    }

    public function __construct($pes_aller_filename, $pes_aquit_completename, $pes_aquit_filename)
    {
        $this->pes_aller_filename = $pes_aller_filename;
        $this->pes_aquit_completename = $pes_aquit_completename;
        $this->pes_aquit_filename = $pes_aquit_filename;
    }
}
