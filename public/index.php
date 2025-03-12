<?php

// Configuration
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once("../init/init.php");
LegacyObjectsManager::setLegacyObjectInstancier();
/** @var \S2lowLegacy\Class\HTMLLayoutFactory $HTMLLayoutFactory */
$HTMLLayoutFactory = LegacyObjectsManager::getLegacyObjectInstancier()->get(HTMLLayoutFactory::class);

$doc = $HTMLLayoutFactory->createHTMLLayout('xhtml_home.tpl.php');

$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
