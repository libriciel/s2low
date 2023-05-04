<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosFilesFactory
{
    private $helios_file_upload;
    private $helios_response_root;

    public function __construct(
        HeliosTransactionsSQL $heliosTransactionsSQL,
        $helios_files_upload_root,
        $helios_responses_root
    ) {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->helios_file_upload = $helios_files_upload_root;
        $this->helios_response_root = $helios_responses_root;
    }

    public function get($transaction_id): HeliosFilesNames
    {
        $info = $this->heliosTransactionsSQL->getInfo($transaction_id);
        return new HeliosFilesNames(
            $this->helios_file_upload . "/{$info['sha1']}",                       // PES ALLER
            $this->helios_file_upload . "/{$info['complete_name']}",          // PES ALLER utilisé lors de l'envoi, normalement supprimé
            $this->helios_response_root . "/" . $info['acquit_filename']   // Acquit, utilisé par setAcquitFilename
        );
    }
}
