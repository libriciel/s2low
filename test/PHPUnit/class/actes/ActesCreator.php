<?php

use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class ActesCreator
{
    private $actesTransactionsSQL;
    private $actesEnvelopeSQL;

    private $last_envelope_id;

    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        $actes_files_upload_root
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actes_files_upload_root = $actes_files_upload_root;
    }

    public function createTransaction(int $status, ?string $archive_path)
    {
        if (is_null($archive_path)) {
            $archive_name = uniqid(rand(), true);
        } else {
            $archive_name = basename($archive_path);
            copy($archive_path, $this->actes_files_upload_root . "/$archive_name");
        }

        $this->last_envelope_id = $this->actesEnvelopeSQL->create(1, "/$archive_name");

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
