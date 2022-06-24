<?php

require_once(__DIR__ . "/../../../init/init-www.php");

if (! $droit->isSuperAdmin($userInfo)) {
    header("Location: index.php");
    exit;
}


$recuperateur = new Recuperateur($_POST);

$id = $recuperateur->get("id");
$siren = $recuperateur->get("siren");

$siren = preg_replace("#\s#", "", $siren);

$authorityGroup = new GroupSQL($sqlQuery);
;

if (!(is_numeric($id) && floatval($id) == intval(floatval($id)))) {
    $_SESSION["error"] = "L'id fournie n'est pas un entier.";
    header("Location: " . WEBSITE_SSL);
    exit;
}

if (empty($authorityGroup->getInfo($id))) {
    $_SESSION["error"] = "Le groupe $id n'existe pas.";
    header("Location: " . WEBSITE_SSL);
    exit;
}

if (mb_strlen($siren) != 9) {
    $_SESSION["error"] = "Le siren ne semble  pas valide.";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=$id");
    exit;
}

$theSiren  = new Siren(new LuhnKey());

if (! $theSiren->isValid($siren)) {
    $_SESSION["error"] = "Le siren ne semble  pas valide.";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=$id");
    exit;
}


$authorityGroupSirenSQL = new AuthorityGroupSirenSQL($sqlQuery);

if ($authorityGroupSirenSQL->exist($id, $siren)) {
    $_SESSION["error"] = "Le siren existe déjà dans ce groupe";
    header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=$id");
    exit;
}

$authorityGroupSirenSQL->add($id, $siren);
$_SESSION["error"] = "Le siren a été ajouté";
header("Location: " . WEBSITE_SSL . "/admin/groups/admin_group_edit.php?id=$id");
