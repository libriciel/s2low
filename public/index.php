<?php

// Configuration
use S2low\Kernel;
use S2lowLegacy\Class\HTMLLayout;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Dotenv())->bootEnv("/data/config/.env");

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

$doc = new HTMLLayout('xhtml_home.tpl.php');

$doc->setTitle(WEBSITE_TITLE);
$doc->buildFooter();
$doc->display();
