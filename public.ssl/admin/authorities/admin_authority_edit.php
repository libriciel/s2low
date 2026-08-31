<?php

use S2low\Security\Authorization\ModuleAdministration;
use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\AvailableSirensByGroup;
use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\AuthorityTypesSQL;
use S2lowLegacy\Model\GroupSQL;

list($objectInstancier, $availableSirensByGroup, $moduleAdministration ) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, AvailableSirensByGroup::class, ModuleAdministration::class]
    );
$html = '';
$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("connexion-status"));
    exit();
}

if (! $me->isAnyAdmin()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getVarFromGet("id");


// Mode modification ou pas
$mod = false;
$authority = new Authority();

$modStr = "Ajout";
if (isset($id)) {
    if (!is_numeric($id)) {
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->returnAndExit(
            1,
            "[admin_authority_edit.php] id doit être un entier, $id fourni",
            WEBSITE_SSL
        );
    }
    $authority->setId($id);
    if ($authority->init()) {
        $modStr = "Modification";
        $mod = true;
    } else {
        $authority = new Authority();
    }
}

if (! $mod && ! $me->isGroupAdminOrSuper()) {
    $_SESSION["error"] = "Accès refusé.";
    header("Location: " . WEBSITE_SSL);
    exit();
}

// Vérification permission sur la collectivité
if (($mod && $me->isGroupAdmin() && ! $authority->isInGroup($me->get("authority_group_id"))) || ($me->isAuthorityAdmin() && $authority->getId() != $me->get("authority_id"))) {
    $_SESSION["error"] = "Accès refusé pour la modification de cette collectivité";
    header("Location: " . WEBSITE_SSL);
    exit();
}

/** @var ObjectInstancier $objectInstancier */
/** @var AuthorityTypesSQL $authorityTypesSQL */

$authorityTypesSQL = $objectInstancier->get(AuthorityTypesSQL::class);
$authority_types_info = $authorityTypesSQL->getInfo($authority->get("authority_type_id"));
try {
    $authority_type_name = $authority_types_info['id'] . "&nbsp;-&nbsp;" . $authority_types_info['description'] ;
} catch (Exception $e) {
    $authority_type_name = null;
}

/****************/

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));

$doc->setTitle("Tedetis : " . $modStr . " collectivité");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion collectivités";

if ($me->isGroupAdmin()) {
    $myGroup = new Group($me->get("authority_group_id"));
    $html .= " du groupe " . get_hecho($myGroup->get("name"));
}

$html .= "</h1>\n";

if ($me->isGroupAdminOrSuper()) {
    $html .= "<p id=\"back-transaction-btn\"><a href=\"" . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authorities.php\" class=\"btn btn-default\">Retour liste collectivités</a></p>\n");
}

$html .= "<h2>" . $modStr . " collectivité</h2>\n";
$html .= "<form class=\"form form-horizontal\" action=\"" .
    \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink(
        "/admin/authorities/admin_authority_edit_handler.php\" enctype=\"multipart/form-data\"  method=\"post\" name=\"form\" onsubmit=\"javascript:return validateForm("
        . $authority->getValidationTrio('name', 'siren', 'agreement', 'email', 'broadcast_email', 'default_broadcast_email', 'status', 'authority_type_id', 'address', 'postal_code', 'city', 'department', 'district', 'telephone', 'fax', 'email_mail_securise')
        . ")\">\n"
    );

if ($mod) {
    $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $authority->getId() . "\" />\n";
    $html .= "<input type=\"hidden\" name=\"mode\" value=\"modify\" />\n";
} else {
    $html .= "<input type=\"hidden\" name=\"mode\" value=\"create\" />\n";
}

$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Nom</label>\n";
$html .= "   <div class=\"col-md-6\">\n";
if ($me->isGroupAdminOrSuper()) {
    $html .= "<input type=\"text\" class=\"form-control\" name=\"name\" value=\"" . get_hecho($authority->get("name")) . "\" />\n";
} else {
    $html .= "<input type=\"text\" class=\"form-control\" disabled=\"disabled\" value=\"" . get_hecho($authority->get("name")) . "\" />\n";
}

$html .= " </div>\n";
$html .= " </div>\n";

//TODO: le mot de passe n'est pas caché, il faut soit faire un md5 sur
// le mot de passe et ici on affiche pas mot de passe.
// quand il chnange on le cripte par md5 et le sauvgarder dans base de donné.
//en ce moment je pas le temp de tout faire et je laiss pour après.
//********************************
$isHeliosAdmin = $moduleAdministration->isHeliosAdmin((int)$authority->getId());

if ($isHeliosAdmin && $authority->getModulePermByName("helios")) {
    $ftpLabel = "HELIOS" . ($authority->get('helios_use_passtrans') ? " [Passtrans]" : "") . " ftp Dest";
    $html .= " <div class=\"form-group\">\n";
    $html .= "  <label class=\"control-label col-md-4\">$ftpLabel</label>\n";
    $html .= "  <div class=\"col-md-6\"><input class=\"form-control\"  type=\"text\" name=\"helios_ftp_dest\" value=\"" . get_hecho($authority->get("helios_ftp_dest")) . "\" /></div>\n";
    $html .= " </div>\n";
}
//********************************

if ($me->isSuper()) {
    list($groups, $sirensByGroup) = $availableSirensByGroup->get($authority->get('id'));
    $html .= " <div class=\"form-group\">\n";
    $html .= "  <label for=\"authority_group_id\" class=\"control-label col-md-4\">Groupe</label>\n";
    $html .= "  <div class=\"col-md-6\">\n";
    $html .= $doc->getHTMLSelect("authority_group_id", $groups, $authority->get('authority_group_id'));
    $html .= "<input type=\"hidden\" id=\"sirensByGroupArray\" value='" . json_encode($sirensByGroup) . "' />\n";
    $html .= "  </div>\n";
    $html .= " </div>\n";
}
$html .= " <div class=\"form-group\">\n";
$html .= "  <label for=\"sirenId\" class=\"control-label col-md-4\">Numéro de SIREN</label>\n";

if ($me->isGroupAdminOrSuper()) {
    if ($modStr == "Ajout") {
        $group = new Group($me->get('authority_group_id'));
    } else {
        $group = new Group($authority->get("authority_group_id"));
    }
    $sirenList = $group->getAuthorizedSiren();
    $html .= "  <div class=\"col-md-6\">";
    $html .= "  <input id=\"originalSiren\" type =\"hidden\" value = \"" . $authority->get('siren') . '"/>';
    $html .= "<select id=\"SelectSirenInput\" class=\"form-control\" name=\"siren\">";
    foreach ($sirenList as $siren_tmp) {
        if ($siren_tmp == $authority->get("siren")) {
              $html .= " <option value =\"$siren_tmp\" selected=\"selected\">$siren_tmp</option>";
        } else {
             $html .= " <option value =\"$siren_tmp\" >$siren_tmp</option>";
        }
    }
    $html .= " </select></div>\n";
} else {
    $html .= "<div class=\"col-md-6\"><input type=\"text\" class=\"form-control\" disabled=\"disabled\" value=\"" . get_hecho($authority->get("siren")) . "\" />\n</div>\n";
}

$html .= " </div>\n";
$html .= "<script src=\"" . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink('/jsmodules/handleSirenGroupe.js') . "\"></script>";

//************


$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Type de collectivité</label>\n";
$html .= "  <div class=\"col-md-6 link-input\">\n";

if ($me->isGroupAdminOrSuper()) {
    $html .= "  <input  class=\"form-control\" type=\"hidden\" id=\"authority_type\" name=\"authority_type_id\" value=\"" . $authority->get("authority_type_id") . "\" />\n";
    $html .= "  <a class=\"link_white\" href=\"#tedetis\" onclick=\"javascript:window.open('" .
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)
            ->getLink(
                "/common/select_popup.php?type=authority_type', 'Selectattribut', 'location=no,scrollbars=yes,menubar=no,status=no,toolbar=no,directories=no,width=512,height=560');\" id=\"authority_type_text\">"
            );

    if ($authority->get("authority_type_id")) {
        $html .= $authority_type_name;
    } else {
        $html .= "[&nbsp;Choisir un type&nbsp;]";
    }
    $html .= "</a>\n";
} else {
    $html .= "<input type=\"text\" class=\"form-control\" disabled=\"disabled\" value=\"" . $authority_type_name . "\" />\n";
}

$html .= " </div>\n";
$html .= " </div>\n";

if ($me->isGroupAdminOrSuper()) {
    $html .= " <div class=\"form-group\">\n";
    $html .= "  <label class=\"control-label col-md-4\">État</label>\n";
    $html .= "  <div class=\"col-md-6\">\n";

    $html .= $doc->getHTMLSelect("status", $authority->get("statusTypes"), $authority->get("status"));

    $html .= "  </div>\n";
    $html .= " </div>\n";
}

$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Adresse électronique «&nbsp;métier&nbsp;»</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"email\" value=\"" . get_hecho($authority->get("email")) . "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Adresse électronique de diffusion par défaut</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\" class=\"form-control\"  name=\"default_broadcast_email\" value=\"" . get_hecho($authority->get("default_broadcast_email")) . "\" size=\"30\" maxlength=\"600\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Adresse électronique de diffusion d'informations</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\" class=\"form-control\"  name=\"broadcast_email\" value=\"" . get_hecho($authority->get("broadcast_email")) . "\" size=\"30\" maxlength=\"2000\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Adresse</label>\n";
$html .= "  <div class=\"col-md-6\">\n";
$html .= "   <textarea  class=\"form-control\" name=\"address\">" . get_hecho($authority->get("address")) . "</textarea>\n";
$html .= "  </div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Code postal</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"postal_code\" value=\"" . get_hecho($authority->get("postal_code")) . "\" size=\"30\" maxlength=\"20\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Ville</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"city\" value=\"" . get_hecho($authority->get("city")) . "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Département&nbsp;/&nbsp;Arrondissement</label>\n";
$html .= "  <div class=\"col-md-6 link-input\">";
$html .= "  <input type=\"hidden\" id=\"department\" name=\"department\" value=\"" . $authority->get("department") . "\" />\n";
$html .= "  <input type=\"hidden\" id=\"district\" name=\"district\" value=\"" . $authority->get("district") . "\" />\n";

if ($me->isGroupAdminOrSuper()) {
    $html .= "  <a class=\"link_white\"href=\"#tedetis\"  class=\"form-control\" onclick=\"javascript:window.open('" .
        \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
            ->get(\S2low\Helpers\RequeteHelper::class)
            ->getLink(
                "/common/select_popup.php?type=department', 'Selectattribut', 'location=no,scrollbars=yes,menubar=no,status=no,toolbar=no,directories=no,width=300,height=560');\" id=\"department_text\">"
            );

    if ($authority->getDeptDistrString()) {
        $html .= $authority->getDeptDistrString();
    } else {
        $html .= "[&nbsp;Choisir un département/arrondissement&nbsp;]";
    }
    $html .= "</a>\n";
} else {
    $html .= $authority->getDeptDistrString();
}

$actesConventions = $objectInstancier->get(ActesConventions::class);

$html .= "  </div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Téléphone</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"telephone\" value=\"" . get_hecho($authority->get("telephone")) . "\" size=\"30\" maxlength=\"20\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Fax</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"fax\" value=\"" . get_hecho($authority->get("fax")) . "\" size=\"30\" maxlength=\"20\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Description pour le module de mail sécurisé:</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"descr_mail_securise\" value=\"" . get_hecho($authority->get("descr_mail_securise")) . "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Adresse électronique pour le module de mail sécurisé:</label>\n";
$html .= "  <div class=\"col-md-6\"><input type=\"text\"  class=\"form-control\" name=\"email_mail_securise\" value=\"" . get_hecho($authority->get("email_mail_securise")) . "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";



$html .= "  <label class=\"control-label col-md-4\">Convention @ctes:</label>\n";
if ($actesConventions->hasConvention($id)) {
    $html .= "<div class=\"col-md-6 alert alert-info\">
        <a href='" . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/admin/authorities/admin_authority_download_convention.php?authority_id=" . $id . "'>") .
            $actesConventions->getConventionFilename($id) .
        "</a></div>";
} else {
    $html .= "<div class=\"col-md-6 alert alert-warning\">Aucune convention trouvée</div>";
}
$html .= " </div>\n";

if ($me->isGroupAdminOrSuper()) {
    $html .= " <div class=\"form-group\">\n";
    $html .= "  <label class=\"control-label col-md-4\">&nbsp;</label>\n";

    $html .= "  <div class=\"col-md-6\"><input type=\"file\" class=\"form-control\" name=\"convention_actes\" /></div>\n";
    $html .= " </div>\n";
}



if (HELIOS_DO_NOT_VERIFY_NOM_FIC_UNICITY && $me->isSuper()) {
    $html .= " <div class=\"form-group\">\n";
    $html .= "  <label class=\"control-label col-md-4\">Unicité la balise NomFic (PES)</label>\n";
    $html .= "  <div class=\"col-md-6\">";
    $html .= $doc->getHTMLSelect(
        "helios_do_not_verify_nom_fic_unicity",
        array(
                'f' => "Vérification de l'unicité de la balise NomFic",
                't' => "Pas de vérification de l'unicité de la balise NomFic - DÉCONSEILLÉ"),
        $authority->get('helios_do_not_verify_nom_fic_unicity') ?: 'f'
    );
    $html .= "</div>";
    $html .= " </div>\n";
}

//echo $authority->get('helios_do_not_verify_nom_fic_unicity');

if ($me->isGroupAdminOrSuper()) {
    $modules = Module::getActiveModulesList();

    $html .= " <div class=\"form-group\">\n";
    $html .= "  <label class=\"control-label col-md-4\">Modules autorisés</label>\n";
    $html .= "  <div class=\"col-md-6\">";

  //$me->canGrantModule($module["name"]
    foreach ($modules as $module) {
        if ($module["id"] == Module::HELIOS && ! $isHeliosAdmin) {
            continue;
        }
        $html .= "<label>" . $doc->getHTMLCheckbox("perm_" . $module["id"], $authority->getModulePerm($module["id"]));
        $html .= "&nbsp;" . $module["description"] . "</label><br />\n";
    }

    $html .= "  </div>\n";
    $html .= " </div>\n";
}

$html .= "<div class=\"form-group row\">\n";
$html .= " <div class=\"offset-md-4 col-md-6\">\n";
$html .= "  <button type=\"submit\" class=\"btn btn-default w-100\">" . (($mod) ? "Valider les modifications" : "Ajouter la collectivité") . "</button>\n";
$html .= " </div>\n";
$html .= "</div>\n";
$html .= "</form>\n";

if ($me->isSuper()) {
    $html .= "<div><a class=\"btn btn-primary\" href='admin_authority_sae.php?id=" . $id . "'>Configurer la connexion SAE »</a></div>";
}

$html .= "<br/>";
$html .= "<div><a class=\"btn btn-primary\" href='admin_authority_siret.php?id=" . $id . "'>Configurer les numéros SIRET »</a></div>";

if ($me->isSuper()) {
    $html .= "<br/><div><a class=\"btn btn-primary\" href='" . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/modules/actes/admin/actes_force_classifiction.php?authority_id=" . $id . "'>Envoyer une demande de classification</a></div>");
    $html .= "<br/><div><a class=\"btn btn-primary\" href='" . \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2low\Helpers\RequeteHelper::class)->getLink("/modules/actes/admin/actes_force_classifiction.php?force=1&authority_id=" . $id . "'>Envoyer demande de classification vide</a></div>");
}
if ($me->isGroupAdminOrSuper()) {
    $html .= "<br><div><a href='/admin/users/admin_users.php?authority=$id'>Liste des utilisateurs de la collectivité</a></div>";
}

$html .= "<br><div><a href='/modules/actes/index.php?authority=$id'>Transactions Actes de la collectivité</a></div>";
$html .= "<br><div><a href='/modules/helios/index.php?authority=$id'>Transactions Helios de la collectivité</a></div>";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
