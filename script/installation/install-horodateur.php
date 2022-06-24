<?php

require_once(__DIR__ . "/../../init/init.php");
require_once(__DIR__ . "/../../docker-resources/S2lowBootstrap.class.php");

ObjectInstancierFactory::getObjetInstancier()->get(S2lowLogger::class)->enableStdOut(true);
$s2lowBootstrap = ObjectInstancierFactory::getObjetInstancier()->get(S2lowBootstrap::class);
$s2lowBootstrap->installHorodateur();
