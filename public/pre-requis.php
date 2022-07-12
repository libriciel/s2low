<?php

require_once("../init/init.php");

LegacyObjectsManager::setLegacyObjectInstancier();

$doc = new HTMLLayout('xhtml_prerequis.tpl.php');



$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
