<?php

use S2lowLegacy\Class\HTMLLayout;

$doc = new HTMLLayout('xhtml_prerequis.tpl.php');



$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
