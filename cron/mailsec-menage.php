#! /usr/bin/php
<?php

use S2lowLegacy\Class\mailsec\MailsecMenageWorker;
use S2lowLegacy\Class\WorkerScript;

require_once(__DIR__ . "/../init/init.php");
$workerScript = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(WorkerScript::class);

$workerScript->scriptByClassName(
    MailsecMenageWorker::class,
    true,
    true
);
