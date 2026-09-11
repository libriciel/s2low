<?php

// Instanciation du module courant
use S2lowLegacy\Class\DatePicker;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

$module = new Module();
if (!$module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$pdo = LegacyObjectsManager::getLegacyObjectInstancier()->get(PDO::class);
$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$HR = new HeliosRetour();

$fstatus = Helpers :: getVarFromGet("status");

if ($fstatus !== '0' && $fstatus != 1) {
    $fstatus = 2;
}

$fmin_submission_date = Helpers :: getVarFromGet("min_submission_date");
$fmax_submission_date = Helpers :: getVarFromGet("max_submission_date");
$fnum = Helpers :: getVarFromGet("num");
$fauthority = Helpers :: getVarFromGet("authority");

$filter = [];
// Construction chaine de filtrage
//filtre sur état
if (isset($fstatus) && is_numeric($fstatus) && $fstatus != 2) {//si = 2 : tous les états
    $filter[] = "status = $fstatus";
}

//collectivité (si sadmin)
if (!$me->isGroupAdminOrSuper()) { // Le super utilisateur voit les reponses de toutes les collectivité
    // Un utilisateur ne voit que les reponses de sa collectivité
    $filter[] = "helios_retour.authority_id=" . $pdo->quote($me->get('authority_id'));
} elseif ($me->isGroupAdmin()) {
    if (isset($fauthority) && $fauthority !== '') {
        // A group administrator may only filter on the authorities of their own group.
        if (!array_key_exists($fauthority, $me->getAllPossibleAuthority())) {
            $_SESSION['error'] = 'Accès refusé';
            header('Location: ' . Helpers::getLink('/modules/helios/helios_retour.php'));
            exit();
        }
        $filter[] = "helios_retour.authority_id=" . $pdo->quote($fauthority);
    } else {
        $filter[] = "helios_retour.authority_id=" . $pdo->quote($me->get('authority_id'));
    }
} else {
    if (isset($fauthority) && $fauthority !== '') {
        $filter[] = "helios_retour.authority_id=" . $pdo->quote($fauthority);
    }
}
// On ajoute les filtres relatifs aux dates
if (isset($fmin_submission_date) && !empty($fmin_submission_date)) {
    $filter[] = "date >= " . $pdo->quote($fmin_submission_date);
}
if (isset($fmax_submission_date) && !empty($fmax_submission_date)) {
    $filter[] = "date <= " . $pdo->quote($fmax_submission_date);
}
//on ajoute filtre sur nom fichier
if (isset($fnum) && !empty($fnum)) {
    $filter[] = "filename LIKE " . $pdo->quote('%' . $fnum . '%');
}

$where = "";
if (count($filter) > 0) {
    $where = " WHERE " . implode(" AND ", $filter);
}


$etat = array(
        0 => "non lu",
        1 => "lu"
        );

$envelops = $HR->getDocumentList($where);

$doc = new HTMLLayout();
$doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/jquery.js") . '"></script>');
$doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/jqueryui.js") . '"></script>');
$doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/select2.js") . '"></script>');
$doc->addHeader('<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>');


$doc->setTitle("Module helios : message retour ");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->buildPager($HR);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Helios - Dématérialisation de documents financiers</h1>\n";

if ($module->getParam("paper") == "on") {
    $html .= "<p>Le système est actuellement en mode &nbsp;papier&nbsp;. Dans ce mode il est impossible de créer de nouvelle transaction. Les transferts doivent se faire par les moyens classiques.</p>\n";
} else {
    $html .= "<p id=\"back-user-btn\"><a href=\"" . Helpers::getLink("/modules/helios/index.php") . "\" class=\"btn btn-default\" title=\"afficher la liste des transactions\">Retour liste transactions</a></p>\n";
}

//filtrage aria
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('filtering_area');\">Filtrage</h2>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<form class=\"form-horizontal\" action=\"" . Helpers::getLink("/modules/helios/helios_retour.php") . "\" method=\"get\">\n";

if (empty($fstatus)) {
    $fstatus = 0; //par defaut état selectionnée
}

$html .= "<div class=\"form-group\">\n";
$html .= "<label class=\"col-md-3 control-label\" for=\"status\">Etat</label>\n";
$html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("status", array(0 => "non lu", 1 => "lu", 2 => "tous les états"), $fstatus) . "</div>\n";
$html .= "<label class=\"col-md-3 control-label\" for=\"filename-contain\">Le nom de fichier contient</label>";
$html .= "<div class=\"col-md-3\"><input id=\"filename-contain\" class=\"form-control\" type=\"text\" name=\"num\" size=\"20\" maxlength=\"25\"";

//$fnum: le nom du fichier contient...
if (strlen($fnum) > 0) {
    $html .= " value=\"" . $fnum . "\"";
}

$html .= " /></div>\n</div>\n";

//des autres options...
//les dates
//date minimale de postage
$html .= "<div class=\"form-group\">\n";
$html .= "<label class=\"col-md-3\" for=\"min_submission_date\">Date de réception minimale</label>\n";
$html .= "<div class=\"col-md-3\">";
$datePickerMin = new DatePicker('min_submission_date', $fmin_submission_date);
$html .= $datePickerMin->show();
$html .= "</div>\n";

$html .= "<label class=\"col-md-3\" for=\"max_submission_date\">Date de réception maximale</label>\n";
$html .= "<div class=\"col-md-3\">";
$datePickerMax = new DatePicker('max_submission_date', $fmax_submission_date);
$html .= $datePickerMax->show();

$html .= "</div>\n</div>\n";


//colectivitïvité  pour superuser
if ($me->isGroupAdminOrSuper()) {
    ob_start();
    ?>
    <div class="form-group">

        <label for="authority" class="col-md-3 control-label">Collectivité</label>

        <div class="col-md-3">
            <select class="form-control zselect_authorities" name="authority">
                <?php if ($me->isSuper()) :
                    ?><option value="">Toutes</option><?php
                endif; ?>
                <?php foreach ($me->getAllPossibleAuthority() as $key => $val) : ?>
                    <option
                        value="<?php hecho($key) ?>" <?php echo (strcmp($key, $fauthority) == 0) ? " selected='selected'" : ""; ?>>
                        <?php hecho($val) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php
    $html .= ob_get_contents();
    ob_end_clean();

    /*$html .= "<div class=\"form-group\">\n";
    $html .= "<label class=\"col-md-3 control-label\" for=\"authority\">Collectivité</label>\n";
    $cond = " ORDER BY authorities.name ASC";
    $html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("authority", Authority :: getAuthoritiesIdName( " ORDER BY authorities.name ASC"), $fauthority) . "</div>\n</div>\n";*/
}
$html .= "<div class=\"form-group\">";
$html .= "    <button type=\"submit\" class=\"col-md-offset-3 col-md-3 btn btn-default\">Filtrer</button>";
$html .= "    <a href=\"" . Helpers::getLink("/modules/helios/helios_retour.php") . "\" class=\"col-md-offset-3 col-md-3 btn btn-default\">Remise à zéro</a>";
$html .= "</div>";

$html .= "</form>\n";
$html .= "</div>\n"; //filtrage aria

$html .= "<h2>Liste des messages retours</h2>\n";

if (count($envelops) > 0) {
    $html .= "<div id=\"retours-area\">\n";
    $html .= "<table class=\"data-table table table-striped\" summary=\"Ce tableau présente respectivement le nom de fichier, la date, l'état, et un lien vers les actions disponibles de chaque message retour Helios\">\n";
    $html .= " <caption>Liste des messages retours Helios en fonction des choix de filtrage</caption>\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";
    $html .= "  <th>Nom du fichier</th>\n";
    $html .= "  <th>Date de réception</th>\n";
    $html .= "  <th>État</th>\n";
    $html .= "  <th>Action</th>\n";
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";
    foreach ($envelops as $envelope) {
        $retour_id = $envelope["id"];

        $html .= "<tr>\n";
        $html .= " <td> <a href=\"" . Helpers::getLink("/modules/helios/helios_download_response.php?id=" . $retour_id) . "\" title=\"Télécharger l'acquittement\">" . $envelope["filename"] . "</a> </td> \n";
        $html .= " <td>" . Helpers::getDateFromBDDDate($envelope["date"], true) . "</td>\n";
        $html .= " <td>" . $etat[$envelope["status"]] . "</td>\n";
        $html .= " <td> ";
        if ($envelope["status"] == 0 && !$me->isGroupAdminOrSuper()) {
            $html .= "<a href=\"" . Helpers::getLink("/modules/helios/helios_change_status_retour.php?id=" . $retour_id) . "\" title=\"passer à l'état lu\" class=\"icon\"> <img alt=\"ok\" src=\"../../custom/images/icone_ok.gif\"> </a> ";
        }
        $html .= "</td>\n";
        $html .= "</tr>\n";
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
        $html .= "</div>\n";
} else {
    $html .= "Pas de transaction trouvée correspondant aux critères de filtrage.";
}


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
