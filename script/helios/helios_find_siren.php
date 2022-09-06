<?php

use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;

require_once(__DIR__ . "/../../init/init.php");
list($objectInstancier, $sqlQuery) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class]
    );

libxml_use_internal_errors(true);

if (count($argv) < 2) {
    echo "Usage: {$argv[0]} min_id\n";
    echo "Affiche le numéro de la transaction, l'identifiant de la collectivité et le SIRET trouvé dans le PES_Aller\n";
    echo "min_id est le numéro minimum (exclu) de l'id de transaction a checker\n\n";
    exit;
}

$min_id = $argv[1];

$sql = "SELECT authority_id,sha1,id FROM helios_transactions WHERE id>? ORDER BY id";
$transactions_list = $sqlQuery->query($sql, $min_id);

echo count($transactions_list) . " transactions trouvées\n";

/** @var PesAllerRetriever $pesAllerRetriever */
$pesAllerRetriever = $objectInstancier->get("PesAllerRetriever");



foreach ($transactions_list as $transaction_info) {
    try {
        $line = "{$transaction_info['id']};{$transaction_info['authority_id']};";
        $filename = $pesAllerRetriever->getPath($transaction_info['sha1']);

        if (!file_exists($filename)) {
            throw new Exception("file not found");
        }


        $xml = simplexml_load_file($filename, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (!$xml) {
            throw new Exception("unable to parse");
        }
        $siret = strval($xml->{'EnTetePES'}->{'IdColl'}['V']);
        $line  .= "$siret";
    } catch (Exception $e) {
        $line .= "ERROR : " . $e->getMessage();
    }
    echo $line . "\n";
}
