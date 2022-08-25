<?php

use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\User;

unset($_SESSION['error']);

$me = new User();

if (! $me->authenticate(Authentification::AUTHENTIFICATION_BY_FORM)) {
    $_SESSION["error"] = "Échec de l'authentification";
}

header("Location: " . WEBSITE_SSL);
