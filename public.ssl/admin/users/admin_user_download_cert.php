<?php

// Configuration
use S2lowLegacy\Class\User;

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("connexion-status"));
    exit();
}

if (!$me->isAnyAdmin()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

if (!$id) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}


$him = new User($id);
$him->init();
$certificate = $him->get("certificate");

if (!$certificate) {
    $_SESSION["error"] = "Pas de certificate";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$output_file = $him->get('id') . "_certificate.pem";

header('Pragma: public');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Content-Transfer-Encoding: none');
header('Content-Type: application/octetstream; name="' . $output_file . '"'); //This should work for IE & Opera
header('Content-Type: application/octet-stream; name="' . $output_file . '"'); //This should work for the rest
header('Content-Disposition: attachment; filename="' . $output_file . '"');
header("Content-length: " . mb_strlen($certificate));

echo $certificate;
