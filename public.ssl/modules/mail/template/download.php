<?php

use S2lowLegacy\Lib\FrontController;

require_once(__DIR__ . '/../../../../init/init.php');
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();
$frontController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);

$frontController->go("MailsecDownload", "download");
