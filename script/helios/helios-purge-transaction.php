<?php
require_once(__DIR__ . "/../../init/init.php");

/**
 * Appeller le script avec l'argument "do" permet de ne pas poser la question pour chaque transaction
 */

$delete_all = (isset($argv[1]) && $argv[1] == 'do');

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("helios-purge-transaction");
$s2lowLogger->enableStdOut(true);

$date=date("Y-m-d",strtotime(sprintf("-%d days",HELIOS_RETENTION_FICHIERS_NB_JOURS)));

$s2lowLogger->info(sprintf("Transaction passé à l'état information disponible avant le %s",$date));

$sql = "SELECT helios_transactions.id FROM helios_transactions_workflow 
    JOIN helios_transactions ON helios_transactions_workflow.transaction_id=helios_transactions.id 
                                    AND helios_transactions_workflow.status_id = helios_transactions.last_status_id 
    WHERE helios_transactions_workflow.date < ? AND helios_transactions_workflow.status_id=? ";

$transaction_ids = $sqlQuery->queryOneCol($sql, $date,HeliosStatusSQL::INFORMATION_DISPONIBLE);

$s2lowLogger->info(sprintf("%d transactions trouvées à détruire",count($transaction_ids)));

$heliosTransactionSQL = $objectInstancier->get(HeliosTransactionsSQL::class);

foreach($transaction_ids as $transaction_id){
    $s2lowLogger->info("Traitement de la transaction $transaction_id");
    $info = $heliosTransactionSQL->getInfo($transaction_id);

    $pes_aller_filename = HELIOS_FILES_ROOT."/{$info['sha1']}";
    $pes_aquit_filename = HELIOS_RESPONSES_ROOT."/{$info['acquit_filename']}";
    $pes_aquit_completename = HELIOS_FILES_ROOT."/{$info['complete_name']}";
    if ($delete_all || ask("Voulez-vous supprimer la transaction $transaction_id  ? (oui/non)")){
        if (file_exists($pes_aquit_completename)){
            unlink($pes_aquit_completename);
            $s2lowLogger->info("Le fichier $pes_aquit_completename a été supprimé");
        }
        if (file_exists($pes_aller_filename)){
            unlink($pes_aller_filename);
            $s2lowLogger->info("Le fichier $pes_aller_filename a été supprimé");
        }
        if (file_exists($pes_aquit_filename)){
            unlink($pes_aquit_filename);
            $s2lowLogger->info("Le fichier $pes_aquit_filename a été supprimé");
        }

        $heliosTransactionSQL->updateStatus(
            $transaction_id,
            HeliosStatusSQL::DETRUITE,
            "Destruction de la transaction"
        );
        $s2lowLogger->info("Les fichiers de la transaction $transaction_id ont été détruits");
    }
}

function ask($question){
    echo "$question";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    return (trim($line) == 'oui');
}
