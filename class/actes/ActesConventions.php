<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Model\AuthoritySQL;

class ActesConventions
{
    public function __construct(
        private readonly AuthoritySQL $authoritySQL,
        private readonly ActesWorkspace $actes_workspace
    ) {
    }

    public function hasConvention($authority_id)
    {
        $filepath = $this->getConventionFilepath($authority_id);
        if (! $filepath) {
            return false;
        }
        return file_exists($filepath);
    }

    public function getConventionFilename($authority_id)
    {
        $filepath = $this->getConventionFilepath($authority_id);
        if (!$filepath) {
            throw new Exception("Aucune convention présente pour la collectivité $authority_id");
        }
        return basename($filepath);
    }

    public function getConventionFilepath($authority_id)
    {
        if (! $authority_id) {
            return false;
        }
        $authority_info = $this->authoritySQL->getInfo($authority_id);
        if (! $authority_info || empty($authority_info['siren'])) {
            return false;
        }
        $siren = $authority_info['siren'];
        return $this->actes_workspace->getActesFilesUploadRoot() . "/$siren/$siren-convention-actes.pdf";
    }

    public function setConvention($authority_id, $filepath)
    {
        $destination_path = $this->getConventionFilepath($authority_id);
        if (! $destination_path) {
            throw new Exception("Impossible de récupérer le chemin de la convention pour la collectivité $authority_id");
        }
        $dirname = dirname($destination_path);
        if (!file_exists($dirname)) {
            if (! mkdir($dirname)) {
                throw new Exception("Impossible de créer le répertoire $dirname");
            }
        }
        if (! copy($filepath, $destination_path)) {
            throw new Exception("Impossible de copier le fichier $filepath vers $destination_path");
        }
    }
}
