<?php

require_once("../../../init/init.php");

$id = Helpers :: getVarFromGet("trans_id");

if (empty($id)) {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit();
}

//FIXME : mettre ca dans un script d'initialisation ....

// Instanciation du module courant
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
    header("Location: " . Helpers::getLink("/modules/actes/index.php"));
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
    header("Location: " . Helpers::getLink("/modules/actes/index.php"));
    exit();
}

//passer les paramètre
$bordereauPdfGenerator = $objectInstancier->get(BordereauPdfGenerator::class);
$bordereauPdfGenerator->generate($id, "acquittement.pdf", false);
