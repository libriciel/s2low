<?php

// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();
$me->logout();

header("Location: " . WEBSITE_SSL . "/login.php");
