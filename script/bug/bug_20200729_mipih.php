<?php

require_once(__DIR__ . "/../../init/init.php");
list($objectInstancier, $sqlQuery) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, SQLQuery::class]
    );

/**
 * Le script BL de purge ne purge pas les pes_aquit
 * Par contre, le état MIPIH ne correspondent pas à ce qui existe réellement sur s2low...
 * Appeller le script avec l'argument "do" permet de ne pas poser la question pour chaque transaction
 */

$delete_all = (isset($argv[1]) && $argv[1] == 'do');

$s2lowLogger = $objectInstancier->get(S2lowLogger::class);
$s2lowLogger->setName("helios-purge-transaction");
$s2lowLogger->enableStdOut(true);

$sql = "select id,acquit_filename from helios_transactions WHERE last_status_id=22;";

$result = $sqlQuery->query($sql);

$s2lowLogger->info(sprintf("%d transactions détruites trouvées", count($result)));

foreach ($result as $info) {
    $s2lowLogger->info("Traitement de la transaction {$info['id']}");
    $pes_aquit_filename = HELIOS_RESPONSES_ROOT . "/{$info['acquit_filename']}";

    if (! file_exists($pes_aquit_filename)) {
        $s2lowLogger->info("Le fichier $pes_aquit_filename n'existe pas");
        continue;
    }

    if ($delete_all || ask("Voulez-vous supprimer le fichier $pes_aquit_filename  ? (oui/non)")) {
        unlink($pes_aquit_filename);
        $s2lowLogger->info("Le fichier $pes_aquit_filename a été supprimé");
    }
}


function ask($question)
{
    echo "$question";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    return (trim($line) == 'oui');
}
