<?php

use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Lib\Recuperateur;

require_once(dirname(__FILE__) . "/../../../init/init-www-actes.php");
//require_once(__DIR__."/../../../class/actes/ActesTransactionsSQL.class.php");

if (! $droit->isSuperAdmin($userInfo)) {
    header("Location: index.php");
    exit;
}
$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get('id');

$actesScriptHelper  = $objectInstancier->get(ActesScriptHelper::class);

$actesScriptHelper->updateStatus(
    [$id],
    ActesStatusSQL::STATUS_EN_ERREUR,
    "Transaction passée manuellement en erreur"
);



$_SESSION['error'] = "La transaction $id a été passée en erreur.";
header("Location: actes_transac_show.php?id=$id");
