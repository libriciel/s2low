<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\helios\PesAllerRetriever;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Controller\HeliosSAEController;
use S2lowLegacy\Model\AuthoritySQL;

list($heliosSAEController, $pesAllerRetriever ) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [HeliosSAEController::class, PesAllerRetriever::class]
    );

$module = new Module();
if (! $module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Éhec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

try {
    $id = Helpers::getIntFromGet("id", true);
} catch (Exception $e) {
    $_SESSION["error"] = "id doit être un entier";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}



$myAuthority = new Authority($me->get("authority_id"));


$trans = new HeliosTransaction();

if (isset($id) && ! empty($id)) {
    $trans->setId($id);
    if ($trans->init()) {//obtine inregistrarea ce corespunde
        $owner = new User($trans->get("user_id")); //!!!!! din HeliosTransaction
        $owner->init();
    } else {
        $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
        header("Location: " . Helpers::getLink("/modules/helios/index.php"));
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, "helios");

if (! $permission->canView($me, $owner)) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . Helpers::getLink("/modules/helios/index.php"));
    exit();
}

$authoritySQL = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(AuthoritySQL::class);
$authority_info = $authoritySQL->getInfo($trans->get('authority_id'));


if ($me->isSuper()) {
    $link_authority = Helpers::getLink("admin/authorities/admin_authority_edit.php?id={$authority_info['id']}");
    $authority_td = "<a href='$link_authority'>" . get_hecho($authority_info['name']) . "</a>";

    $link_user = Helpers::getLink("/admin/users/admin_user_edit.php?id=") . $owner->getId();
    $user_td = "<a href='$link_user'>" . get_hecho($owner->get("givenname") . " " . $owner->get("name")) . "</a>";
} else {
    $authority_td = get_hecho($authority_info['name']);
    $user_td = get_hecho($owner->get("givenname") . " " . $owner->get("name"));
}


$doc = new HTMLLayout();

$doc->setTitle("Helios : visualisation de transactions pour un fichier");
$doc->addBody("<div class=\"container\"><div class=\"row\">");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$currentStatus = HeliosTransactionWorkflow::getCurrentStatus($id);

$user_id = $trans->get('user_id');
$userInfo = new User($user_id);
$userInfo->init();

$authorityInfo = new Authority($userInfo->get("authority_id"));
$authorityInfo->init();


$html = "<p id=\"back-transaction-btn\"><a href=\"" . Helpers::getLink("/modules/helios/index.php") . "\" class=\"btn btn-default\">Retour liste transactions</a></p>\n";
$html .= "<h2>Détails de la transaction</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline("Fichier", $trans->getFilenameForID($id));
if ($trans->get('xml_nomfic')) {
    $html .= $doc->getHTMLArrayline("Nom du fichier posté (balise NomFic)", $trans->get('xml_nomfic'));
    $html .= $doc->getHTMLArrayline("Code collectivité (codcol)", $trans->get('xml_cod_col'));
    $html .= $doc->getHTMLArrayline("Code budget (codbud)", $trans->get('xml_cod_bud'));
    $html .= $doc->getHTMLArrayline("Identifiant du poste comptable (idPost)", $trans->get('xml_id_post'));
}
$html .= $doc->getHTMLArrayline("Date de postage", Helpers :: getDateFromBDDDate(HeliosTransactionWorkflow::getDatePoste($id), true));
$html .= $doc->getHTMLArrayline("État actuel", $currentStatus);
$html .= $doc->getHTMLArrayline("Taille (octets)", $trans->get("file_size"));
$html .= $doc->getHTMLArrayline("Empreinte SHA1", $trans->get("sha1"));
$html .= $doc->getHTMLArrayline("Suivie par", $user_td);
$html .= $doc->getHTMLArrayline("Collectivité", $authority_td);


if ($trans->get("sae_transfer_identifier")) {
    $url_pastell = preg_replace("#(/api/?)$#", "", $authority_info['pastell_url']);
    $url_pastell = "$url_pastell/Document/detail?id_e={$authority_info['pastell_id_e']}&id_d=" . $trans->get("sae_transfer_identifier");
    $html .= $doc->getHTMLArrayline("URL sur Pastell (archivage)", "<a href='$url_pastell' target='_blank'>$url_pastell</a>");
}

$arch_url = $trans->get("archive_url");

if (!empty($arch_url)) {
      $url = "<a href=\"" . $trans->get("archive_url") . "\">" . get_hecho($trans->get("archive_url")) . "</a>";
} else {
    $url = "Non définie";
}
$html .= $doc->getHTMLArrayline("URL d'archivage", $url);
$html .= "</table>\n";
$html .= "</div>\n";


$html .= "<h2>Récuperation du fichier posté ";
$html .= "<a href=\"" . Helpers::getLink("/modules/helios/helios_download_file.php?id=" . $id) . "\" title=\"Télécharger le fichier\">" . $trans->getFilenameForID($id) . "</a> </h2>";


if ($me->isSuper()) {
    $link = Helpers::getLink("/modules/helios/helios_transac_validate_pes_aller.php?id=$id");
    $html .= "<a href='$link'>Validation XML du fichier</a>";
}

// Affichage du Workflow

$workflow = $trans->fetchWorkflow();
$status = HeliosTransaction::getStatusList();

$html .= "<h2>Cycle de vie de la transaction</h2>\n";

if (count($workflow) > 0) {
    $html .= "<table class=\"data_table table table-striped\">\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";
    $html .= "  <th id=\"status\">Etat</th>\n";
    $html .= "  <th id=\"date\">Date</th>\n";
    $html .= "  <th id=\"message\">Message</th>\n";
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";
    foreach ($workflow as $stage) {
        $html .= " <tr>\n";
        $html .= "  <td headers=\"status\">" . $status[$stage["status_id"]] ;
        $html .= "</td>\n";
        $html .= "  <td headers=\"date\">" . Helpers::getDateFromBDDDate($stage["date"], true) . "</td>\n";
        $html .= "  <td headers=\"message\" class=\"long_field\">" . nl2br($stage["message"]) . "</td>\n";
        $html .= " </tr>\n";
    }
    $html .= " </tbody>\n";
    $html .= "</table>\n";
} else {
    $html .= "Le cycle de vie est vide pour cette transaction.\n";
}

if ($trans->get('acquit_filename')) {
    $html .= " <a href=\"" . Helpers::getLink("/modules/helios/helios_download_acquit.php?id=" . $id) . "\" title=\"Télécharger l'acquittement\">Télécharger le PES Acquit</a> ";
}


$currentStatusId = HeliosTransactionWorkflow::getCurrentStatusId($id);

$actionHtml = "";
if (in_array($currentStatusId, array(8,4,11,20))) {
    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/helios/helios_transac_archiver.php") . "\"  method=\"post\">\n";
    $actionHtml .= "<div class=\"form-group row align-items-center\">\n<label class=\"col-md-4 control-label\">Archivage SEDA : </label>\n";
    $actionHtml .= "<div class=\"col-md-8\">\n";
    $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Versement manuel\" />\n";
    $actionHtml .= "</div>\n";
    $actionHtml .= "</div>\n</form>\n";
    $actionHtml .= "</div>\n";
}

if ($currentStatusId == HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE) {
    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/helios/helios_transac_send_sae.php") . "\"  method=\"post\">\n";
    $actionHtml .= "<div class=\"form-group row align-items-center\">\n<label class=\"col-md-4 control-label\">Archivage SEDA : </label>\n";
    $actionHtml .= "<div class=\"col-md-8\">\n";
    $actionHtml .= "<input type=\"hidden\" name=\"transaction_id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Envoyer la transaction sur Pastell\" />\n";
    $actionHtml .= " (Attention, peut être très long.) \n";
    $actionHtml .= "</div>\n";
    $actionHtml .= "</div>\n</form>\n";
    $actionHtml .= "</div>\n";
}

if ($currentStatusId == HeliosStatusSQL::ENVOYER_AU_SAE) {
    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/helios/helios_transac_verif_sae.php") . "\"  method=\"post\">\n";
    $actionHtml .= "<div class=\"form-group row align-items-center\">\n<label class=\"col-md-4 control-label\">Archivage SEDA : </label>\n";
    $actionHtml .= "<div class=\"col-md-8\">\n";
    $actionHtml .= "<input type=\"hidden\" name=\"transaction_id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Vérifier la transaction sur Pastell\" />\n";
    $actionHtml .= "</div>\n";
    $actionHtml .= "</div>\n</form>\n";
    $actionHtml .= "</div>\n";
}

$status_cible_list = $heliosSAEController->getActionPossible($currentStatusId);
foreach ($status_cible_list as $new_status_id) {
    $libelle_status = HeliosStatusSQL::getStatusLibelle($new_status_id);
    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/helios/helios_transac_change_status_sae.php") . "\" method=\"post\" onsubmit=\"return confirm('Voulez-vous vraiment mettre cette transaction en état $new_status_id ?.');\">\n";
    $actionHtml .= "<div class=\"form-group row align-items-center\"><label class=\"col-md-4 control-label\">&nbsp;</label>\n";
    $actionHtml .= "<div class=\"col-md-8\">\n";
    $actionHtml .= "<input type=\"hidden\" name=\"transaction_id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"hidden\" name=\"status_id\" value=\"" . $new_status_id . "\" />\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-warning\" value=\"Forcer le status « $libelle_status »\" /> \n";
    $actionHtml .= "</div>\n";
    $actionHtml .= "</div>\n</form>\n";
    $actionHtml .= "</div>\n";
}

if ($me->isSuper()) {
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/helios/helios_transac_delete.php") . "\" onsubmit=\"return confirm('Cette transaction sera éradiquée DEFINITIVEMENT de la base sans espoir de retour?')\" method=\"post\">\n";
    $actionHtml .= "<div class=\"form-group row align-items-center\">\n<label class=\"col-md-4 control-label\">Effacer de la base de donnée (TRES DANGEREUX) : </label>\n";
    $actionHtml .= "<div class=\"col-md-8\">\n";
    $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />\n";
    $actionHtml .= "<input type=\"submit\" value=\"Effacer de la base de données\" class=\"btn btn-danger\" />\n";
    $actionHtml .= "</div>\n";
    $actionHtml .= "</div></form>\n";

    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/helios/helios_transac_set_error.php") . "\" onsubmit=\"return confirm('Cette transaction sera passée en erreur ')\" method=\"post\">\n";
    $actionHtml .= "<div class=\"form-group row align-items-center\">\n<label class=\"col-md-4 control-label\">Passer la transaction en erreur </label>\n";
    $actionHtml .= "<div class=\"col-md-8 d-flex align-items-center\">\n";
    $actionHtml .= "<input type=\"text\" placeholder=\"Message\" name=\"message\" class=\"form-control me-2\" style='width: auto !important; max-width: 250px;'/>";
    $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />\n";
    $actionHtml .= "<input type=\"submit\" value=\"Passer en erreur\" class=\"btn btn-warning\" style='white-space: nowrap;' />";
    $actionHtml .= "</div>\n";
    $actionHtml .= "</div></form>\n";

    if (in_array($currentStatusId, array(2,3,-1,7))) {
        ob_start();?>

    <form
        action="<?php echo Helpers::getLink("/modules/helios/helios_transac_rollback.php");?>"
        method="post"
        onsubmit="return confirm('Êtes-vous certain de vouloir faire cela ?')"
        >
        <div class="form-group row align-items-center">
            <label class="col-md-4 control-label">Repasser la transaction en « posté »</label>
            <div class="col-md-8">
                <input type="hidden" name="id" value="<?php hecho($id) ?>"/>
                <input type="submit" value="Revenir en arrière" class="btn btn-danger" />
            </div>
        </div>


    </form>


        <?php
        $actionHtml .= ob_get_contents();
        ob_end_clean();
    }
}


if (isset($actionHtml)) {
    $html .= "<h2>Actions</h2>\n";
    $html .= $actionHtml;
}

if ($currentStatusId == 14 && $me->checkDroit($module->get("name"), 'TT')) {
    ob_start(); ?>
<h3>Télétransmission du fichier</h3>
<p>
<form action='<?php echo WEBSITE_SSL?>modules/helios/helios_transac_submit.php' id='form_sign' method='post'>
    <input type='hidden' name='id'  value='<?php echo $id?>'/>
    <input class='submit_button' type='submit' value='Télétransmettre'/>

</form>
</p>
    <?php
        $html .= ob_get_contents();
        ob_end_clean();
}

$html .= "</div>\n";
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
