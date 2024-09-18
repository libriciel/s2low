<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;

$me = new User();
$me->logout();

header('Location: ' . Helpers::getLink('/login.php'));
exit;
