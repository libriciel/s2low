<?php

//Liste des clients qui n'ont pas un certificat RGS

use S2low\Services\CertificateStores\Stores;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\RgsCertificate;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);
/** @var Stores $certificatesStores */
$certificatesStores = LegacyObjectsManager::getLegacyObjectInstancier()->get(Stores::class);
$rgsCertificate = new RgsCertificate(OPENSSL_PATH, $certificatesStores->getDefaultStorePath());

$handle = fopen("php://output", "w");


$date = '2016-01-01';

$sql = "SELECT DISTINCT actes_transactions.user_id FROM actes_transactions WHERE decision_date > ? AND type='1' ";

$sql_user = "SELECT users.id,certificate,givenname,users.name,users.email, users.authority_id, authorities.name as authority_name, users.authority_group_id, authority_groups.name as group_name FROM users " .
            " LEFT JOIN authorities ON users.authority_id = authorities.id " .
            " LEFT JOIN authority_groups ON users.authority_group_id = authority_groups.id " .
            " WHERE users.id=?";

echo $sql_user;


foreach ($sqlQuery->query($sql, $date) as $line) {
    $info = $sqlQuery->queryOne($sql_user, $line['user_id']);

    if (! $rgsCertificate->isRgsCertificate($info['certificate'])) {
        unset($info['certificate']);
        fputcsv($handle, $info);
    }
}

fclose($handle);
