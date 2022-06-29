<?php

// Configuration
require_once("../init/init.php");

$me = new User();
$me->logout();

header("Location: " . WEBSITE_SSL . "/login.php");
