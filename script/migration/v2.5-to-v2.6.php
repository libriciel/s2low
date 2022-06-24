<?php

/*Parcours toutes les transactions Helios et ajoute les info CodCol, CodBud et IdPost dans la base*/
// @deprecated en v5.0 ( NE PAS UTILISER ) ( A supprimer ? )

require_once(__DIR__ . "/../../init/init.php");

libxml_use_internal_errors(true);

$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);

$sql = "SELECT authority_id,sha1,id,last_status_id FROM helios_transactions WHERE helios_transactions.xml_cod_col IS NULL";
$transactions_list = $sqlQuery->query($sql);

echo count($transactions_list) . " transactions trouvées\n";

foreach ($transactions_list as $transaction_info) {
    try {
        echo "Transaction numéro {$transaction_info['id']} : ";
        $filename = HELIOS_FILES_UPLOAD_ROOT . "/" . $transaction_info['sha1'];

        if (in_array($transaction_info['last_status_id'], array(HeliosTransactionsSQL::POSTE))) {
            echo "transaction posté : PASS\n";
            continue;
        }
        if (!file_exists($filename)) {
            throw new Exception("file not found");
        }

        $pes_xml = simplexml_load_file($filename, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (!$pes_xml) {
            throw new Exception("unable to parse");
        }
        $info['nom_fic'] = utf8_decode(strval($pes_xml->Enveloppe->Parametres->NomFic['V'])); // @deprecated en v5.0
        $info['cod_col'] = strval($pes_xml->EnTetePES->CodCol['V']);
        $info['cod_bud'] = strval($pes_xml->EnTetePES->CodBud['V']);
        $info['id_post'] = strval($pes_xml->EnTetePES->IdPost['V']);

        $heliosTransactionSQL->setInfoFromPESAller($transaction_info['id'], $info);

        echo "OK\n";
    } catch (Exception $e) {
        echo "ERROR : " . $e->getMessage() . "\n";
    }
}
