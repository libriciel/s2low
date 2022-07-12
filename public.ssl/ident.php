<?php

unset($_SESSION['error']);

$me = new User();

if (! $me->authenticate(Authentification::AUTHENTIFICATION_BY_FORM)) {
    $_SESSION["error"] = "Échec de l'authentification";
}

header("Location: " . WEBSITE_SSL);
