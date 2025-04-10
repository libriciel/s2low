<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosFilesFactory
{
    public function __construct(
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly Workspace $workspace
    ) {
    }

    public function get($transaction_id): HeliosFilesNames
    {
        $info = $this->heliosTransactionsSQL->getInfo($transaction_id);
        return new HeliosFilesNames(
            $this->workspace->getHeliosFilesUploadRoot() . "/{$info['sha1']}",                       // PES ALLER
            $this->workspace->getHeliosFilesUploadRoot() . "/{$info['complete_name']}",          // PES ALLER utilisé lors de l'envoi, normalement supprimé
            $this->workspace->getHeliosResponsesRoot() . "/" . $info['acquit_filename']   // Acquit, utilisé par setAcquitFilename
        );
    }
}
