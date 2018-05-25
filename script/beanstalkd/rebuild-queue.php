<?php

require_once __DIR__."/../../init/init.php";

$actesAntivirus = $objectInstancier->get(ActesAntivirus::class);
$actesAntivirus->rebuildQueue();