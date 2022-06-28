<?php

require_once("../config/config.php");

$doc = new HTMLLayout('xhtml_prerequis.tpl.php');



$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
