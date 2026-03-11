<?php

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;

require_once("../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

/** @var HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = LegacyObjectsManager::getObject(HTMLLayoutFactory::class);

$doc = $htmlLayoutFactory->createLayout('xhtml_prerequis.tpl.php');



$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
