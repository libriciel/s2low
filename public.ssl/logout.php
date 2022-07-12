<?php

// Configuration
require_once("../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();

$me = new User();
$me->logout();

header("Location: " . Helpers::getLink("/login.php"));
