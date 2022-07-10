<?php

// Configuration
require_once("../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();

$doc = new HTMLLayout('xhtml_home.tpl.php');

$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
