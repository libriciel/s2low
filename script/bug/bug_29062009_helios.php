<?php

exit;
//Correction du bug survenu le 29/06/2009
//La plateforme n'a pas convenablement horodaté l'ensemble des changement d'état
//08h32min22s à 18h16min27s


require_once(dirname(__FILE__) . "/../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$db = DatabasePool::getInstance();

$sql = "SELECT helios_transactions_workflow.*, " .
        " helios_status.name as status_name, helios_status.id as status_id, " .
        " helios_transactions.user_id " .
        " FROM helios_transactions_workflow " .
        " JOIN helios_status ON helios_transactions_workflow.status_id = helios_status.id" .
        " JOIN helios_transactions ON helios_transactions_workflow.transaction_id = helios_transactions.id " .
        " WHERE DATE(date)='2010-06-29'";

$result = $db->select($sql);

while ($row = $result->get_next_row()) {
    $msg = "Transaction " . $row['transaction_id'] . " " . getStatusName($row['status_id'], $row['status_name']);

    echo $msg . " - " . $row['user_id'];

    $sql = "SELECT count(*) as count FROM logs WHERE message='" . addslashes($msg) . "'";
    $r2 = $db->select($sql);
    $cpt = $r2->get_next_row();
    if ($cpt['count']) {
        echo " -- Déjà dans la base";
    } else {
        Log::newEntry('TdT', $msg, 1, $row['date'], 'USER', 'helios', false, $row['user_id']);
        echo " -- Ajouté dans la base";
    }
    echo "\n";
}

function getStatusName($status_id, $status_name)
{

    switch ($status_id) {
        case 1:
            return "en cours de traitement";
        case 2:
            return "dans la file d'attente";
        case 3:
            return "transmise au serveur";
        case 4:
            return "acceptee";
        case 7:
            return "en cours de traitement";
    } ;


    return $status_name;
}

function horodate($msg, $id_u)
{
    $sql = "INSERT INTO logs(date,severity,module,issuer,user_id,visibilite,message,timestamp) VALUES ()";
}
