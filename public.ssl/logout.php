<?php

// Configuration
require_once("../init/init.php");

$me = new User();
$me->logout();

header("Location: " . Helpers::getLink("/login.php"));
