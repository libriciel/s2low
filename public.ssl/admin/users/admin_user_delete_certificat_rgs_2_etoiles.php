<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

$userSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(UserSQL::class);

$me = new User();

$api = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("api");

function exitOrDisplayError($api, $erreur_msg, $location)
{
    if ($api) {
        $jsonOutput = new JSONoutput();
        $jsonOutput->displayErrorAndExit($erreur_msg);
    } else {
        $_SESSION["error"] = $erreur_msg;
        header("Location: $location ");
        exit;
    }
}

if (! $me->authenticate()) {
    exitOrDisplayError($api, "Échec de l'authentification", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
}

if (! $me->isAdmin()) {
    exitOrDisplayError($api, "Accès refusé", WEBSITE_SSL);
}

$id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("id");

$him = new User();
$him->setId($id);
if (! $him->init()) {
    exitOrDisplayError($api, "Erreur lors de la modification de l'utilisateur", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php"));
} else {
    if (! $me->canEditUser($id)) {
        exitOrDisplayError($api, "Accès refusé pour la modification de cet utilisateur", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php"));
    }
}

$user_info = $userSQL->getInfo($him->getId());
$x509Certificate = new X509Certificate();
$certificat_connexion_info = $x509Certificate->getInfo($user_info['certificate']);
if ($userSQL->hasDoublon($him->getId(), $certificat_connexion_info, $user_info['login'], false)) {
    exitOrDisplayError($api, "Impossible de supprimer le certificat car l'opération entrainerait des doublons", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_user_edit.php?id={$him->getId()}"));
}
$userSQL->deleteCertificateRGS2Etoiles($him->getId());


$msg = "Modification ";
$msg .= " de l'utilisateur " . $him->getPrettyName() . " (id=" . $him->getId() . "). Résultat ok.";
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
    $msg .= "\nErreur de journalisation.";
}


if ($api) {
    $jsonOutput = new JSONoutput();
    $jsonOutput->display(array('status' => 'ok','message' => $msg,'id' => $him->getId()));
} else {
    $_SESSION["error"] = nl2br($msg);
    \S2lowLegacy\Class\Helpers\SessionHelper::purgeTempSession();
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_user_edit.php?id=") . $him->getId());
}
