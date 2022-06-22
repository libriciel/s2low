<?php

use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . "/../../init/init.php";

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->enableStdOut();
$s2lowLogger->setName("actes-export");


if (count($argv) < 3) {
    $s2lowLogger->error(
        sprintf(
            "Usage: %s output_directory min_transaction_date max_transaction_date [TAMPON]",
            $argv[0]
        )
    );
    $s2lowLogger->error(
        "Exporte l'ensemble des transactions actes" .
        "dans le répertoire output_directory entre min_transaction_date (inclu) et max_transaction_date (exclu).\n" .
        "Si le 4eme argument est TAMPON, alors les fichiers seront tamponnés"
    );
    exit(-1);
}

$output_directory = $argv[1];
$min_transaction_date = $argv[2];
$max_transaction_date = $argv[3];
$tampon = $argv[4] ?? false;

$actesExport = $objectInstancier->get(ActesExport::class);
$actesExport->setTamponnerFichier($tampon);

$sql = "SELECT authorities.siren,authorities.name,actes_transactions.authority_id,min(actes_transactions.id) as min_id,max(actes_transactions.id) as max_id,count(actes_transactions.id) as count FROM actes_transactions_workflow " .
    " JOIN actes_transactions ON actes_transactions_workflow.transaction_id=actes_transactions.id " .
    " JOIN authorities ON actes_transactions.authority_id=authorities.id " .
    " WHERE date>=? AND date <=? AND status_id=?" .
    " GROUP BY actes_transactions.authority_id,authorities.siren,authorities.name";

try {
    $authority_info_list = $sqlQuery->query(
        $sql,
        $min_transaction_date,
        $max_transaction_date,
        ActesStatusSQL::STATUS_POSTE
    );

    foreach ($authority_info_list as $authority_info) {
        $s2lowLogger->info(
            sprintf(
                "Export des transactions de la collectivité %s (%s) entre les transactions %d et %d",
                $authority_info['name'],
                $authority_info['siren'],
                $authority_info['min_id'],
                $authority_info['max_id']
            )
        );
        $filesystem = new Filesystem();
        $filesystem->mkdir($output_directory . "/{$authority_info['siren']}");

        $actesExport->export(
            $authority_info['authority_id'],
            $output_directory . "/{$authority_info['siren']}",
            $authority_info['min_id'],
            $authority_info['max_id']
        );
    }
} catch (Exception $e) {
    $s2lowLogger->error($e->getMessage());
    $s2lowLogger->error($e->getTraceAsString());
    exit(-2);
}

$s2lowLogger->info("Export terminé");
exit(0);
