<?php

require_once(dirname(__FILE__) . "/../../../init/init-www-actes.php");

$recuperateur = new Recuperateur($_GET);

$id = $recuperateur->getInt('id');


$module = new Module();
if (!$module->initByName("actes")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$trans = new ActesTransaction();
$trans->setId($id);
if (! $trans->init()) {
    $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
    header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
    exit();
}

$envelope = new ActesEnvelope($trans->get("envelope_id"));
$envelope->init();

$owner = new User($envelope->get("user_id"));
$owner->init();

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "actes");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
    exit();
}


$actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);


$info = $actesTransactionSQL->getStatusInfo($id, ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

if (! $info) {
    $_SESSION['error'] = "Cette transaction n'existe pas";
    header("Location: index.php");
}


header("Content-type: application/xml");
echo $info['flux_retour'];
