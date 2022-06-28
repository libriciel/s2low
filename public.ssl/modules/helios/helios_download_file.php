<?php

// Configuration
require_once("../../../init/init.php");

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
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

$transaction_id = Helpers :: getVarFromGet("id");


$trans = new HeliosTransaction();

if (isset($transaction_id) && ! empty($transaction_id)) {
    $trans->setId($transaction_id);
    if ($trans->init()) {//obtine inregistrarea ce corespunde
        $owner = new User($trans->get("user_id")); //!!!!! din HeliosTransaction
        $owner->init();
    } else {
        $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
        header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
    exit();
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "helios");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
    exit();
}



$myAuthority = new Authority($me->get("authority_id"));

$entity = new HeliosTransaction($transaction_id);
$filename = $entity->getFilenameForId($transaction_id);
$sha1 = $entity->getSha1ForId($transaction_id);

$ownerId = $entity->getUserForId($transaction_id);
$owner = new User($ownerId);
$owner->init();

try {
    $pesAllerRetriever = $objectInstancier->get("PesAllerRetriever");
    $filepath = $pesAllerRetriever->getPath($sha1);
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur lors de la récupération du fichier : " . $e->getMessage();
    header("Location: " . WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=$transaction_id");
    exit();
}

Helpers::sendFileToBrowser($filepath, $filename, "text/xml");
