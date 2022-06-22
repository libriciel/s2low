<?php

/**
 * Entre la version 1.5 et la version 2.1 les enveloppes soumises directement dont le fichier métier contenait une signature n'ont pas pu avoir
 * cette signature enregistré correctement dans le système.
 *
 * Ce script extrait toutes les transactions entre ces deux dates et vérifie que les enveloppes métier contiennent une signature.
 * Si c'est le cas, alors on enregistre cette signature
 *
 */

$date_debut_bug = "2015-05-01";
$date_fin_bug = "2015-06-20";


require_once(__DIR__ . "/../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/init/init.php');



$sql = "SELECT DISTINCT actes_transactions_workflow.date, actes_transactions.envelope_id, actes_transactions.id as transaction_id, file_path " .
        " FROM actes_transactions_workflow " .
        " JOIN actes_transactions ON actes_transactions_workflow.transaction_id=actes_transactions.id " .
        " JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id " .
        " WHERE date >= ? AND date <= ? AND status_id=1 ORDER BY actes_transactions_workflow.date";

$result = $sqlQuery->query($sql, $date_debut_bug, $date_fin_bug);


$actesEnveloppeSQL = new ActesEnvelopeSQL($sqlQuery);

$actesIncludedFileSQL = new ActesIncludedFileSQL($sqlQuery);


foreach ($result as $info) {
    print_r($info);

    $actes_info = $actesIncludedFileSQL->getActesFileInfo($info['transaction_id']);

    if ($actes_info['signature']) {
        continue;
    }


    $archivePath = ACTES_FILES_UPLOAD_ROOT . '/' . $info['file_path'];

    echo "Etude de : $archivePath\n";

    $tmpFolder = new TmpFolder();
    $tmpDir = $tmpFolder->create();

    $tgzExtractor = new TGZExtractor($tmpDir);

    $tgzExtractor->extract($archivePath, false);

    $xml_file = $actesIncludedFileSQL->getXMLFilename($info['transaction_id']);




    echo "Etude de  : $xml_file\n";
    $xml = simplexml_load_file($tmpDir . "/" . $xml_file);

    $namespaces = $xml->getDocNamespaces();
    $actesItems = $xml->children($namespaces["actes"]);

    $signature = strval($actesItems->Document->Signature);
    if ($signature) {
        print_r($info);
        echo "FOUND !\n";
        //echo $signature;
        echo $info['transaction_id'];
        $actesIncludedFileSQL->setSignature($info['transaction_id'], $actes_info['id'], $signature);
    }

    $tmpFolder->delete($tmpDir);
}
