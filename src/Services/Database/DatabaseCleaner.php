<?php

namespace S2low\Services\Database;

use PDO;
use S2lowLegacy\Lib\SQLQuery;

class DatabaseCleaner
{
    private PDO $pdo;

    public function __construct(SQLQuery $sqlQuery)
    {
        $this->pdo = $sqlQuery->getPDO();
    }

    public function cleanAllTables(): void
    {
        $tableList = [
            "actes_classification_requests",
            "actes_envelope_serials",
            "helios_status",
            "helios_transactions_workflow",
            "mail_included_file",
            "mail_errors",
            "mail_message_emis",
            "mail_transaction",
            "authority_pastell_config",
            "users",
            "logs",
            "users_perms",
            "logs_request",
            "actes_envelopes",
            "actes_transactions",
            "helios_transactions",
            "actes_batch_files",
            "service_user",
            "service_user_content",
            "message_admin",
            "authorities",
            "authority_groups",
            "modules_authorities",
            "actes_type_pj",
            "actes_transactions_workflow",
            "actes_transmission_windows",
            "actes_transmission_window_hours",
            "actes_status",
            "mail_annuaire",
            "mail_user_groupe",
            "authority_siret",
            "logs_historique",
            "helios_retour",
            "nounce",
            "helios_transmission_windows",
            "helios_transmission_window_hours",
            "mail_groupe",
            "actes_batches",
            "actes_included_files",
            "actes_classification_codes",
            "authority_group_siren",
        ];

        foreach ($tableList as $table) {
            $this->pdo->exec("TRUNCATE TABLE $table CASCADE");
        }
    }
}
