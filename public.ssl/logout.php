<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;

$me = new User();
$me->logout();

header('Location: ' . \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/login.php'));
exit;
