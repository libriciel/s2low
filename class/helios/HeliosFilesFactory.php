<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosFilesFactory
{
    public function __construct(
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly string $helios_files_upload_root,
        private readonly string $helios_responses_root,
    ) {
    }

    public function get($transaction_id): HeliosFilesNames
    {
        $info = $this->heliosTransactionsSQL->getInfo($transaction_id);
        return new HeliosFilesNames(
            $this->helios_files_upload_root . "{$info['sha1']}",                       // PES ALLER
            $this->helios_files_upload_root . "{$info['complete_name']}",          // PES ALLER utilisé lors de l'envoi, normalement supprimé
            $this->helios_responses_root . $info['acquit_filename']   // Acquit, utilisé par setAcquitFilename
        );
    }
}
