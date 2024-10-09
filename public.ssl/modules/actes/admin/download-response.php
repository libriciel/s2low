<?php

use S2lowLegacy\Class\actes\ActesResponsesError;
use S2lowLegacy\Lib\Recuperateur;

require_once(__DIR__ . "/../../../../init/init-www-actes.php");

if ($userInfo['role'] != 'SADM') {
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);

$filename = $recuperateur->get('file');


$actesResponsesError = $objectInstancier->get(ActesResponsesError::class);

try {
    $actesResponsesError->download($filename);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: responses-actes-error.php");
    exit();
}
