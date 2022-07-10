<?php

require_once(__DIR__ . "/../../init/init.php");
list($objectInstancier, $sqlQuery) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class]
    );

libxml_use_internal_errors(true);

if ($argc < 2) {
    echo "Usage {$argv[0]} authority_id \n";
    echo "Affiche le SIREN des transactions Helios trouvé dans les PES ALLER d'une collectivité donnée\n\n";
    exit;
}

$authority_id = $argv[1];

$sql = "SELECT id,filename,sha1 FROM helios_transactions WHERE authority_id=?";
$transactions_list = $sqlQuery->query($sql, $authority_id);

echo count($transactions_list) . " transactions trouvées\n";


/** @var PesAllerRetriever $pesAllerRetriever */
$pesAllerRetriever = $objectInstancier->get("PesAllerRetriever");


foreach ($transactions_list as $transaction_info) {
    try {
        echo "Analyse de la transaction {$transaction_info['id']}: ";
        $filename = $pesAllerRetriever->getPath($transaction_info['sha1']);
        if (!file_exists($filename)) {
            throw new Exception("file not found");
        }


        $xml = simplexml_load_file($filename, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (!$xml) {
            throw new Exception("unable to parse");
        }
        $siret = strval($xml->{'EnTetePES'}->{'IdColl'}['V']);
        echo "$siret\n";
    } catch (Exception $e) {
        echo "ERROR : " . $e->getMessage() . "\n";
        continue;
    }
}
