<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class ActesCreator
{
    private ActesTransactionsSQL $actesTransactionsSQL;
    private ActesEnvelopeSQL $actesEnvelopeSQL;
    private $last_envelope_id;

    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_files_upload_root
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->siren = '491011698';
        $this->archive_directory = $this->actes_files_upload_root . '/' . $this->siren;
        mkdir($this->archive_directory);
    }

    public function createTransaction(int $status, ?string $archive_path, string $tmp_dir)
    {
        if (is_null($archive_path)) {
            $archive_name = uniqid((string)rand(), true);
        } else {
            $archive_name = basename($archive_path);
            copy($archive_path, $this->archive_directory . "/$archive_name");
        }

        $this->last_envelope_id = $this->actesEnvelopeSQL->create(1, $this->siren . "/$archive_name");

        $transaction_id = $this->actesTransactionsSQL->create($this->last_envelope_id, $status, 1, 1);

        $this->actesTransactionsSQL->updateStatus(
            $transaction_id,
            $status,
            'Création de la transaction via PHPUNIT'
        );
        return $transaction_id;
    }

    public function getLastEnvelopeId()
    {
        return $this->last_envelope_id;
    }
}
