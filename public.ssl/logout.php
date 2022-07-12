<?php

$me = new User();
$me->logout();

header("Location: " . Helpers::getLink("/login.php"));
