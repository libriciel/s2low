<?php

require_once(__DIR__ . "/../../../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM') {
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}

$recuperateur = new Recuperateur($_GET);

$filename = $recuperateur->get('file');


$heliosResponsesError = new HeliosResponsesError();

try {
    $heliosResponsesError->display($filename);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: responses-helios-error.php");
}
