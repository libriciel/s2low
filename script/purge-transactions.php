<?php


/**
 *
 * @deprecated 4.2.4 use helios-purge-transaction.php instead
 *
 * Outil de purge des transactions.
 *
 * Les transactions candidates Ã  la pure sont
 * - pour actes, Ã  l'Ã©tat
 *          4 (Acquittement reÃ§u)
 *          5 (ValidÃ©)
 *          6 (RefusÃ©)
 * - pour hÃ©lios, Ã  l'Ã©tat
 *          4 (Acquittement reÃ§u = HeliosTransactionsSQL::ACQUITTER)
 *          6 (RefusÃ© = HeliosTransactionsSQL::REFUSER)
 *          8 (Information disponible = HeliosTransactionsSQL::INFORMATION_DISPONIBLE)
 * - passÃ©e Ã  cet Ã©tat depuis au moins HELIOS_RETENTION_FICHIERS_NB_JOURS ou ACTES_RETENTION_FICHIERS_NB_JOURS jours.
 *   Si elles sont plus rÃ©centes, mÃªme dans ces Ã©tats, elles ne sont pas purgÃ©es.
 * Les transactions sont traitÃ©es par ordre d'id.
 * Avant traitement (paramÃ¨tre mode), toutes les transactions candidates sont comptÃ©es et le nombre affichÃ©.
 * ParamÃ¨tres d'appel :
 *      mode : PURGE ou CONTROLE (dÃ©faut)
 *      actes_nb_max : nombre maximum de transactions candidates traitÃ©es; toutes par dÃ©faut. 0 = aucune
 *      helios_nb_max : nombre maximum de transactions candidates traitÃ©es; toutes par dÃ©faut. 0 = aucune
 * Exemples
 *      mode=CONTROLE helios_nb_max=0 actes_nb_max=10
 *          Affiche le nombre de toutes les transactions candidates et
 *          Ne liste que les 10 premiÃ¨res Actes, et aucune hÃ©lios
 *      mode=PURGE
 *          Affiche le nombre de toutes les transactions candidates et
 *          les purge toutes
 *      rien
 *          Affiche le nombre de toutes les transactions candidates et
 *          les liste toutes
 *      mode=CONTROLE helios_nb_max=0 actes_nb_max=0
 *          Affiche le nombre de toutes les transactions candidates et
 *          n'en liste aucune.
 */
error_reporting(error_reporting() & ~E_NOTICE);

const MODE_CONTROLE = 'CONTROLE';
const MODE_PURGE = 'PURGE';

require_once(__DIR__ . "/../init/init.php");

#require_once("/var/www/s2low/model/HeliosTransactionsSQL.class.php");
#require_once(__DIR__ . '/BLBatch.class.php');

/** @var $sqlQuery SQLQuery */
$blScript = new PurgeBatch();

$prm_actes_nb_max = $blScript->getArg('actes_nb_max', PHP_INT_MAX);
$prm_helios_nb_max = $blScript->getArg('helios_nb_max', PHP_INT_MAX);
$prm_mode = $blScript->getArg('mode', MODE_CONTROLE);

if (!in_array($prm_mode, array(MODE_CONTROLE, MODE_PURGE))) {
    $blScript->traceln("Valeur du parametre 'mode' incorrecte : $prm_mode");
    die(1);
}
$blScript->traceln("Mode : $prm_mode");

//################# ACTES

$sql = "SELECT t.id, max(t.envelope_id) envelope_id";
$sql .= " FROM actes_transactions t, actes_transactions_workflow tw";
$sql .= " WHERE (tw.transaction_id = t.id)";
$sql .= " AND (t.last_status_id = tw.status_id)";
$sql .= " AND (t.last_status_id in (" . ActesStatusSQL::STATUS_ACQUITTEMENT_RECU . "," . ActesStatusSQL::STATUS_VALIDE . "," . 6 /* RefusÃ© */  . "))";
$sql .= " AND (t.type = '1')";
$sql .= " GROUP BY t.id";
$sql .= " HAVING (max(tw.date) < (current_timestamp - interval '" . ACTES_RETENTION_FICHIERS_NB_JOURS . " days'))";
$sql .= " ORDER BY t.id;";
$purge_list = $sqlQuery->query($sql);
$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$count_purgeables = count($purge_list);
$count_max = min(array($count_purgeables, $prm_actes_nb_max));
$blScript->traceln("Nombre de transactions actes purgeables : $count_purgeables");
$blScript->traceln("Nombre de transactions actes max : " . ($prm_actes_nb_max == PHP_INT_MAX ? 'toutes' : $prm_actes_nb_max));
$index = 0;
foreach ($purge_list as $data) {
    $blScript->checkBatchStop();
    $index++;
    if ($index > $count_max) {
        break;
    }
    $tid = $data['id'];
    $envelope_id = $data['envelope_id'];
    if ($prm_mode == MODE_CONTROLE) {
        $blScript->traceln("Actes ($index/$count_max) - candidate Ã  la purge - id $tid, envelope $envelope_id");
    } else {
        $msg = "Demande de purge des fichiers";
        $blScript->traceln("Actes ($index/$count_max) - $msg - id $tid, envelope $envelope_id");
        $actesTransactionsSQL->updateStatus($tid, ActesStatusSQL::STATUS_DETRUITE, $msg);
    }
}

////################# HELIOS


//"SELECT count(*) FROM helios_transactions_workflow WHERE date<'2020-05-28' AND status_id=8";

$sql = "SELECT t.id";
$sql .= " FROM helios_transactions t, helios_transactions_workflow tw";
$sql .= " WHERE (tw.transaction_id = t.id)";
$sql .= " AND (t.last_status_id = tw.status_id)";
$sql .= " AND (t.last_status_id in (";
$sql .= HeliosTransactionsSQL::ACQUITTER;
$sql .= "," . HeliosTransactionsSQL::REFUSER;
$sql .= "," . HeliosTransactionsSQL::INFORMATION_DISPONIBLE;
$sql .= "))";
$sql .= " GROUP BY t.id";
$sql .= " HAVING (max(tw.date) < (current_timestamp - interval '" . HELIOS_RETENTION_FICHIERS_NB_JOURS . " days'))";
$sql .= " ORDER BY t.id;";
$purge_list = $sqlQuery->query($sql);
$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$count_purgeables = count($purge_list);
$count_max = min(array($count_purgeables, $prm_helios_nb_max));
$blScript->traceln("Nombre de transactions Helios purgeables : $count_purgeables");
$blScript->traceln("Nombre de transactions Helios max : " . ($prm_helios_nb_max == PHP_INT_MAX ? 'toutes' : $prm_helios_nb_max));
$index = 0;
foreach ($purge_list as $data) {
    $blScript->checkBatchStop();
    $index++;
    if ($index > $count_max) {
        break;
    }
    $tid = $data['id'];
    if ($prm_mode == MODE_CONTROLE) {
        $blScript->traceln("Helios ($index/$count_max) - candidate Ã  la purge - id $tid");
    } else {
        $msg = "Demande de purge des fichiers";
        $blScript->traceln("Helios ($index/$count_max) - $msg - id $tid");
        $heliosTransactionsSQL->updateStatus($tid, HeliosStatusSQL::ADETRUIRE, $msg);
    }
}
