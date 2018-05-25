#! /usr/bin/php
<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$actesAntivirus = $objectInstancier->get(ActesAntivirus::class);
$actesAntivirus->script();
