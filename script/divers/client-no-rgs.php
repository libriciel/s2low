<?php

//Liste des clients qui n'ont pas un certificat RGS

require_once(__DIR__ . "/../../init/init.php");

$rgsCertificate = new RgsCertificate(OPENSSL_PATH, RGS_VALIDCA_PATH);

$handle = fopen("php://output", "w");

$sql = "SELECT users.id,certificate,givenname,users.name,users.email, users.authority_id, authorities.name as authority_name, users.authority_group_id, authority_groups.name as group_name FROM users " .
        " LEFT JOIN authorities ON users.authority_id = authorities.id " .
        " LEFT JOIN authority_groups ON users.authority_group_id=authority_groups.id " .
        " ORDER BY users.id";

$sql_actes = "SELECT max(actes_transactions_workflow.date) FROM actes_transactions " .
        " JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id " .
        " WHERE actes_transactions.user_id=? AND actes_transactions.type = 1";

$sql_helios = "SELECT max(helios_transactions_workflow.date) FROM helios_transactions " .
    " JOIN helios_transactions_workflow ON helios_transactions.id=helios_transactions_workflow.transaction_id " .
    " WHERE helios_transactions.user_id=?";


foreach ($sqlQuery->query($sql) as $line) {
    if (! $rgsCertificate->isRgsCertificate($line['certificate'])) {
        unset($line['certificate']);
        $line['last_acte'] = $sqlQuery->queryOne($sql_actes, $line['id']);
        $line['last_helios'] = $sqlQuery->queryOne($sql_helios, $line['id']);
        fputcsv($handle, $line);
    }
}

fclose($handle);
