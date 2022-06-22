<?php

// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/Layout.class.php');

$doc = new HTMLLayout('xhtml_home.tpl.php');

$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
