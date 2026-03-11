<?php

use S2lowLegacy\Class\actes\ActesIncludedFileSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesTypePJSQL;
use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ModulePermission;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Controller\ActesSAEController;
use S2lowLegacy\Lib\ObjectInstancierFactory;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/** @var Initialisation $initialisation */
/** @var ActesTypePJSQL $actesTypePJSQL */
/** @var SQLQuery $sqlQuery */
/** @var ActesSAEController $actesSAEController */
/** @var UserContext $userContext */
/** @var \S2lowLegacy\Class\HTMLLayoutFactory $htmlLayoutFactory */

list($initialisation,$actesTypePJSQL, $sqlQuery,$actesSAEController, $userContext, $htmlLayoutFactory) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [Initialisation::class, ActesTypePJSQL::class, SQLQuery::class, ActesSAEController::class, UserContext::class, HTMLLayoutFactory::class]
    );

$moduleData = $initialisation->initModule($userContext, Initialisation::MODULENAMEACTES, Initialisation::DROITSACTES);

$loader = new FilesystemLoader(__DIR__ . '/../../../templates');
$twig = new Environment($loader);

$actionHtml = '';

$id = intval(Helpers :: getVarFromGet('id'));
if (empty($id)) {
    $_SESSION['error'] = "Pas d'identifiant de transaction spécifié";
    header_wrapper('Location: ' . Helpers::getLink('/modules/actes/index.php'));
    exit_wrapper();
}

$trans = new ActesTransaction();
$trans->setId($id);
if (! $trans->init()) {
    $_SESSION['error'] = "Erreur d'initialisation de la transaction.";
    header('Location: ' . Helpers::getLink('/modules/actes/index.php'));
    exit_wrapper();
}

$envelope = new ActesEnvelope($trans->get('envelope_id'));
$envelope->init();

$owner = new User($envelope->get('user_id'));
$owner->init();


$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser, 'actes');

if (! $permission->canView($userContext->me, $owner)) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . Helpers::getLink('/modules/actes/index.php'));
    exit_wrapper();
}

$myAuthority = new Authority($userContext->me->get('authority_id'));
$transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

$status_list = ActesTransaction :: getStatusList();
$workflow = $trans->fetchWorkflow();


$transactionTypes = $trans->get('transactionTypes') ;
$transStatus = $trans->getCurrentStatus();

$authoritySQL = ObjectInstancierFactory::getObjetInstancier()->get(AuthoritySQL::class);
$authority_info = $authoritySQL->getInfo($trans->get('authority_id'));

$doc = $htmlLayoutFactory->createLayout();


$doc->addHeader("<script type=\"text/javascript\" src=\"" . Helpers::getLink('/jsmodules/jquery.js') . "\"></script>");
$doc->addHeader("<script type=\"text/javascript\" src=\"" . Helpers::getLink('/jsmodules/jqueryui.js') . "\"></script>");


$doc->setTitle("Tedetis : visualisation d'une transaction");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->closeSideBar();
$doc->openContent();

$html = "<p id=\"back-transaction-btn\"><a href=\"" . Helpers::getLink("/modules/actes/\" class=\"btn btn-default\">Retour liste transactions</a></p>\n");

$html .= "<h2>Détails de la transaction</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline('Type de transaction', $transactionTypes[$trans->get('type')]);
if ($trans->get("type_reponse")) {
    $html .= $doc->getHTMLArrayline('Type de réponse', ActesTransaction::getTypeReponse($trans->get("type"), $trans->get("type_reponse")));
}

if ($userContext->me->isSuper()) {
    $link_authority = Helpers::getLink("admin/authorities/admin_authority_edit.php?id={$authority_info['id']}");
    $authority_td = "<a href='$link_authority'>" . get_hecho($authority_info['name']) . "</a>";

    $link_user = Helpers::getLink("/admin/users/admin_user_edit.php?id=") . $owner->getId();
    $user_td = "<a href='$link_user'>" . get_hecho($owner->get("givenname") . " " . $owner->get("name")) . "</a>";
} else {
    $authority_td = get_hecho($authority_info['name']);
    $user_td = get_hecho($owner->get("givenname") . " " . $owner->get("name"));
}

$html .= $doc->getHTMLArrayline("Collectivité", $authority_td);
$html .= $doc->getHTMLArrayline("Dossier suivi par", $user_td);


// Contenu différent en fonction du type de transaction
switch ($trans->getType()) {
    case TypeTransaction::TransmissionActe:
        $html .= $doc->getHTMLArrayline("Nature de l'acte", $transNatures[$trans->get("nature_code")]);
        $html .= $doc->getHTMLArrayline("Numéro de l'acte", get_hecho($trans->get("number")));
        $html .= $doc->getHTMLArrayline("Date de la décision", Helpers :: getDateFromBDDDate($trans->get("decision_date")));
        $html .= $doc->getHTMLArrayline("Objet", nl2br(get_hecho($trans->get("subject"))));
        $html .= $doc->getHTMLArrayline("Documents papiers complémentaires", $trans->getDocumentPapier() ? "OUI" : "NON");

        $classification = get_hecho($trans->get("classification"));
        if ($trans->get("classification_string")) {
            $classification .= " - " . $trans->get("classification_string");
        }

        $html .= $doc->getHTMLArrayline("Classification matières/sous-matières", $classification);
        $html .= $doc->getHTMLArrayline("Identifiant unique", get_hecho($trans->get("unique_id")));

        $arch_url = $trans->get("archive_url");

        if (!empty($arch_url)) {
            $url = "<a href=\"" . $trans->get("archive_url") . "\">" . get_hecho($trans->get("archive_url")) . "</a>";
        } else {
            $url = "Non définie";
        }
        $html .= $doc->getHTMLArrayline("URL d'archivage", $url);
        if ($trans->get("sae_transfer_identifier")) {
            $url_pastell = preg_replace("#(/api/?)$#", "", $authority_info['pastell_url']);
            $url_pastell = "$url_pastell/Document/detail?id_e={$authority_info['pastell_id_e']}&id_d=" . $trans->get("sae_transfer_identifier");
            $html .= $doc->getHTMLArrayline("URL sur Pastell (archivage)", "<a href='$url_pastell' target='_blank'>$url_pastell</a>");
        }

        if ($trans->get("broadcasted") == 't') {
            $notification = "Notifiée à " . $trans->get("broadcast_emails");
        } else {
            $notification = "Non notifiée";
        }
        $html .= $doc->getHTMLArrayline("Notification", $notification);
        break;

    case TypeTransaction::CourrierSimple:
    case TypeTransaction::DemandePieceComplementaire:
    case TypeTransaction::LettreDObservation:
    case TypeTransaction::DefereAuTribunalAdministratif:
        $related_trans = new ActesTransaction($trans->get("related_transaction_id"));
        $related_trans->init();


        $html .= $doc->getHTMLArrayline("Date de réception du document  ", $related_trans->get("decision_date"));

        if ($related_trans->get("related_transaction_id")) {
            $files = $related_trans->fetchFilesList();
            foreach ($files as $file) {
                $html .= $doc->getHTMLArrayline(
                    "Document reçu   ",
                    "<a href=\"" . Helpers::getLink("/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\">" . $file["posted_filename"] . "</a>")
                );
            }

            $related_trans = new ActesTransaction($related_trans->get("related_transaction_id"));
            $related_trans->init();
            $html .= $doc->getHTMLArrayline("Date de décision de l'acte initial   ", $related_trans->get("decision_date"));
            $html .= $doc->getHTMLArrayline("Acte initial", "<a href=\"" . Helpers::getLink("/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("number") . "</a>"));
        } else {
            $html .= $doc->getHTMLArrayline("Acte ", "<a href=\"" . Helpers::getLink("/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("number") . "</a>"));
        }


        break;

    case TypeTransaction::Annulation:
        $related_trans = new ActesTransaction($trans->get("related_transaction_id"));
        $related_trans->init();

        $html .= $doc->getHTMLArrayline("Acte à annuler", "<a href=\"" . Helpers::getLink("/modules/actes/actes_transac_show.php?id=" . $related_trans->getId() . "\">" . $related_trans->get("unique_id") . "</a>"));
        break;

    case TypeTransaction::DemandeDeClassification:
        break;
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";

// Fichier archive présent ou non ?
$status = $trans->getCurrentStatus();
$archiveDeleted = false;
/*if ($trans->get("type") != 1 || $status > 4 || $status <= 0) {
  $archiveDeleted = true;
}*/
if (
    $trans->isType(TypeTransaction::Annulation) ||

     $status == 5 ||
     $status == 6 ||
     $status == 16
) {
    $archiveDeleted = true;
}
// Fichiers contenus dans la transaction
$html .= "<h2>Fichiers contenus dans l'archive transmise </h2>";

$archiveName = get_hecho(basename($envelope->get("file_path")));

$html .= "<div class=\"data_table\">\n";
$files = $trans->fetchFilesList();

if (is_array($files)) {
    $html .= "<table class=\"table table-bordered table-striped\">\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";
    $html .= "  <th id=\"file\">Fichier</th>\n";
    $html .= "  <th id=\"type\">Type de fichier</th>\n";
    $html .= "  <th id=\"size\">Taille du fichier</th>\n";
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";


    foreach ($files as $file_num => $file) {
        $html .= " <tr>\n";
        $html .= "  <td headers=\"file\" class=\"long_field\">";

        $html .= "<dl>\n";

        if (mb_strlen($file["posted_filename"]) > 0) {
            $html .= "<dt>Nom original&nbsp;:</dt>\n";
            $html .= "<dd>";

            if ($archiveDeleted) {
                $html .=  $file["posted_filename"];
            } else {
                $html .= $file["posted_filename"] . "<br/><a href=\"" . Helpers::getLink("/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\"> [Télécharger le fichier original]</a>") ;
                $html .= "&nbsp;&nbsp;";

                $transactionAEteAcquit = in_array(4, array_column($workflow, 'status_id'));
                if ($transactionAEteAcquit) {
                    if ($file["mimetype"] == "application/pdf") {
                        $date = date("Y-m-d");

                        ob_start();
                        ?>
                        <br/>

                        <a
                                class='telecharger_tampon'
                                href="/modules/actes/actes_download_file.php?tampon=true&file=<?php echo $file["id"] ?>"
                                title="Télécharger le fichier avec tampon"
                        >
                        [Télécharger le fichier tamponné]</a>
                        <input id="tampon_date" type="hidden">

                            <?php
                            $html .= ob_get_contents();
                            ob_end_clean();
                            $firstFichierATamponner = ! isset($datepicker_rendered);
                            if ($firstFichierATamponner) {
                                # On ajoute le datepicker
                                $html .= $twig->render('telechargement_fichier_tamponne.twig');
                                $datepicker_rendered = true;
                            }
                    }
                }

                $html .= "</dd>";
            }
        }

        $html .= "<dt>Nom métier&nbsp;:</dt>\n";
        $html .= "<dd>";

        if (mb_strlen($file["posted_filename"]) <= 0 && !$archiveDeleted) {
            $html .= $file["name"] . "<br/><a href=\"" . Helpers::getLink("/modules/actes/actes_download_file.php?file=" . $file["id"] . "\" title=\"Télécharger le fichier\">[Télécharger]</a>");
        } else {
            $html .= $file["name"];
        }

        $html .= "</dd>";
        $html .= "</dl>\n";

        if ($file['code_pj']) {
            $html .= '<dt>Type de pièce jointe :</dt>';
            $html .= "<dd>" . get_hecho($actesTypePJSQL->getLibelle($file['code_pj']) ?: $file['code_pj']) . "</dd>";
        }

        $html .= "</td>\n";
        $html .= "  <td headers=\"type\" >" . $file["mimetype"] . "</td>\n";
        $html .= "  <td headers=\"size\" >" . $file["size"] . " octets</td>\n";
        $html .= " </tr>\n";
    }
      $html .= "</tbody>\n";
      $html .= "</table>\n";
} else {
    $html .= "  <p>Pas de fichier trouvé</p>";
}

$html .= ($archiveDeleted) ? $archiveName : "Archive transmise : <a href=\"" . Helpers::getLink("/modules/actes/actes_download_file.php?env=" . $trans->get("envelope_id") . "\" title=\"Télécharger l'archive .tar.gz\">" . $archiveName . "</a>");

if ($userContext->me->isSuper()) {
    $link = Helpers::getLink("/modules/actes/actes_transac_validate.php?transaction_id=$id");
    $html .= "<br/><a href='$link'>Validation de l'archive</a>";
}

$html .= "</div>\n";
// Affichage du Workflow

$html .= "<h2>Cycle de vie de la transaction</h2>\n";

if (count($workflow) > 0) {
    $html .= "<div class=\"data_table\">\n";
    $html .= "<table class=\"table-striped table table-bordered\">\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";
    $html .= "  <th id=\"status\">État</th>\n";
    $html .= "  <th id=\"date\">Date</th>\n";
    $html .= "  <th id=\"message\">Message</th>\n";
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";
    $create_pdf_html = "&nbsp;<a href=\"actes_create_pdf.php?trans_id=" . $id . "&user_id=" . $userContext->me->getId() . "\">";
    $create_pdf_html .= "<br/>[Télécharger]</a>";

    $create_pdf_html .= "<br/><a href='actes_transac_get_ARActe.php?id=$id'>[Afficher l'ARActe]</a> ";


    //---fin de modification

    foreach ($workflow as $stage) {
        $html .= "<tr>\n";

        if (in_array($stage["status_id"], array(4,11))) {
              $html .= "  <td headers=\"status\">" . $status_list[$stage["status_id"]] . $create_pdf_html . "</td>\n";
        } else {
            $html .= "  <td headers=\"status\">" . $status_list[$stage["status_id"]] . "</td>\n";
        }

      //---------end

        $html .= "  <td headers=\"date\">" . Helpers :: getDateFromBDDDate($stage["date"], true) . "</td>\n";
        $html .= "  <td headers=\"message\" class=\"long_field\">" . nl2br($stage["message"]) . "</td>\n";
        $html .= " </tr>\n";
    }
    $html .= " </tbody>\n";
    $html .= "</table>\n";
    $html .= "</div>\n";
} else {
    $html .= "Le cycle de vie est vide pour cette transaction.\n";
}

$courrier = $trans->getCourrierInfo();
if (count($courrier) != 0) {
    $html .= "<h2>Document reçu relatif à l'acte</h2>\n";
     $html .= "<div class=\"data_table\">\n";
    $html .= "<table class=\"table data-table table-striped table-bordered\">\n";
    $html .= " <thead>\n";
    $html .= " <tr>\n";
    $html .= "  <th id=\"type\">Type</th>\n";
    $html .= "  <th id=\"sens\">sens</th>\n";
    $html .= "  <th id=\"actions\">action</th>\n";
    $html .= " </tr>\n";
    $html .= " </thead>\n";
    $html .= " <tbody>\n";
    foreach ($courrier as $id => $info) {
          $html .= " <tr>\n";
        $html .= "  <td headers=\"type\">" . $transactionTypes[$info["type"]] . "</td>\n";
        $html .= "  <td headers=\"sens\">" . $info["sens"] . "</td>\n";
        $html .= "  <td headers=\"actions\">
  		<a href=\"" . Helpers::getLink("/modules/actes/actes_transac_show.php?id=" . $id . "\"><img alt=\"pdf\" src=\"../../custom/images/erreur.png\"> </a></td>\n");
        $html .= " </tr>\n";
    }

    $html .= "</tbody>\n";
    $html .= "</table>\n";
    $html .= "</div>\n";
}


if (!$userContext->me->isSuper() && $userContext->me->checkDroit($moduleData->moduleInfo['name'], 'CS') &&  $permission->canWrite($userContext->me, $owner)) {
    $actionHtml = "";


  // Formulaire de notification a posteriori
  // Affichés quand la transaction a été acquittée par le MIAT et non notifiée
    if ($trans->isType(TypeTransaction::TransmissionActe) && $transStatus == 4 && ! $trans->get("broadcasted")) {
      // adresses emails de diffusion


        $org = new Authority($userContext->me->get("authority_id"));
        $defaultbroadcast_email = $org->get("default_broadcast_email");
        if ($defaultbroadcast_email != null) {
            $defaultbroadcast_email = explode(",", $defaultbroadcast_email);
        } else {
            $defaultbroadcast_email = array();
        }

        $broadcast_email = ACTES_COMMON_BROADCAST_EMAILS . "," . $org->get("broadcast_email");
        $broadcast_email = explode(",", $broadcast_email);

        $actionHtml .= "<div class=\"action\">\n";
        $actionHtml .= "<form class=\"form\" action=\"" . Helpers::getLink("/modules/actes/actes_transac_notify.php\" method=\"post\">\n");
        $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Notifier la transaction\" />\n";
        $actionHtml .= "<div class=\"form-group\">\n<label class=\"control-label\">Emission des documents sources : </label><input type=\"checkbox\" class=\"inline-checkbox\" name=\"send_sources\" checked='checked' />\n</div>\n";

        foreach ($defaultbroadcast_email as $email) {
            $checked = 'checked="checked" disabled="disabled"';
            if ($email != "" && $email != null) {
                $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-offset-1 control-label\" for=\"$email\"><input id=\"$email\" type=\"checkbox\" name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</label></div>\n";
            }
        }

        foreach ($broadcast_email as $email) {
            $checked = '';
            if ($email != "" && $email != null) {
                $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-offset-1 control-label\" for=\"$email\"><input type=\"checkbox\"  name=\"broadcast_email[]\" value=\"$email\" " . $checked . " />" . $email . "</label></div>\n";
            }
        }


        $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "</form>\n";
        $actionHtml .= "</div>\n";
    }
}

//On vérifie qu'il n'y a pas de demande d'annulation en cours
if (!$trans->hasPendingCancelTrans()) {
    // Boutons de cloture de la transaction
    // Affichés quand la transaction a été acquittée par le MIAT
    if ($trans->isType(TypeTransaction::TransmissionActe) && $transStatus == 4 && !  $userContext->me->isGroupAdminOrSuper()) {
        if ($trans->canValidate()) {
            $actionHtml .= "<div class=\"action\">\n";
            $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_close.php\" onsubmit=\"return confirm('" . 'Voulez-vous vraiment fermer cette transaction ? Cette action est non réversible et est sous votre entière responsabilité.' . "');\" method=\"post\">\n");
            $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Acte validé par le ministère : </label>\n";
            $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
            $actionHtml .= "<input type=\"hidden\" name=\"status\" value=\"valid\" />\n";
            $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Passer la transaction en état « Validée »\" />\n";
            $actionHtml .= "</div>\n</form>\n";
            $actionHtml .= "</div>\n";
        }//fin if verfiie canValidate

        $actionHtml .= "<div class=\"action\">\n";
        $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_close.php\" onsubmit=\"return confirm('" . 'Voulez-vous vraiment fermer cette transaction ? Cette action est non réversible et est sous votre entière responsabilité.' . "')\" method=\"post\">\n");
        $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Acte refusé par le ministère : </label>\n";
        $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "<input type=\"hidden\" name=\"status\" value=\"invalid\" />\n";
        $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Passer la transaction en état «&nbsp;Refusée&nbsp;»\" />\n";
        $actionHtml .= "</div>\n</form>\n";
        $actionHtml .= "</div>\n";
    }//fin if qui verifie type == 1 et status == 4

    $actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
    $transactionsInfo = $actesTransactionsSQL->getInfo($trans->getId());
    $authoritySQL = new AuthoritySQL($sqlQuery);
    $authorityInfo = $authoritySQL->getInfo($transactionsInfo['authority_id']);

    if ($trans->isType(TypeTransaction::TransmissionActe) && in_array($transStatus, [4,5,14,20]) && $trans->canValidate()) {
         $actionHtml .= "<div class=\"action\">\n";
          $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_archiver.php\"  method=\"post\" id='form_send_sae'>\n");
          $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Archivage SEDA : </label>\n";
          $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
          $actionHtml .= "<input type=\"submit\" id='button_send_sae' class=\"btn btn-primary\" value=\"Versement manuel\" />\n";
          $actionHtml .= "</div>\n</form>\n";
          $actionHtml .= "</div>\n";
        ob_start();
        ?>
        <script type="text/javascript" src="<?php echo Helpers::getLink("/jsmodules/jquery.js"); ?>"></script>

        <script>

    $(document).ready(function(){
        $("#form_send_sae").submit(function(){
            $("#button_send_sae").val("Versement en cours");
            $("#button_send_sae").attr('disabled','disabled');
        });
    });

</script>


        <?php
        $actionHtml .= ob_get_contents();
        ob_end_clean();
    }//fin if type == 1 , status = 4 ou 14, transaction canvalidate et configuration pour le sae
}//fin if qui verifie qu'il n'y a pas d'annulation en cours


if ($userContext->me->isSuper() && $transStatus == ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE) {
    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_force_send_sae.php\" method=\"post\">\n");
    $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Versement SEDA : </label>\n";
    $actionHtml .= "<input type=\"hidden\" name=\"transaction_id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Envoyer au SAE\" /> (Attention, peut-être long)\n";
    $actionHtml .= "</div>\n</form>\n";
    $actionHtml .= "</div>\n";
}

if ($userContext->me->isSuper() && $transStatus == ActesStatusSQL::STATUS_ENVOYE_AU_SAE) {
    $actionHtml .= "<div class=\"action\">\n";
    $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_verif_sae.php\" method=\"post\" >\n");
    $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Versement SEDA : </label>\n";
    $actionHtml .= "<input type=\"hidden\" name=\"transaction_id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Vérifier la transaction sur le SAE\" /> \n";
    $actionHtml .= "</div>\n</form>\n";
    $actionHtml .= "</div>\n";
}

if ($userContext->me->isSuper()) {
    $status_cible_list = $actesSAEController->getActionPossible($transStatus);
    foreach ($status_cible_list as $new_status_id) {
        $libelle_status = ActesStatusSQL::getStatusLibelle($new_status_id);
        $actionHtml .= "<div class=\"action\">\n";
        $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_change_status_sae.php\" method=\"post\" onsubmit=\"return confirm('Voulez-vous vraiment mettre cette transaction en état $new_status_id ?.');\">\n");
        $actionHtml .= "<div class=\"form-group\"><label class=\"col-md-4 control-label\">&nbsp;</label>\n";
        $actionHtml .= "<input type=\"hidden\" name=\"transaction_id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "<input type=\"hidden\" name=\"status_id\" value=\"" . $new_status_id . "\" />\n";
        $actionHtml .= "<input type=\"submit\" class=\"btn btn-warning\" value=\"Forcer le status « $libelle_status »\" /> \n";
        $actionHtml .= "</div>\n</form>\n";
        $actionHtml .= "</div>\n";
    }
}


// Bouton d'annulation en fonction du type et de l'état
// Doit être une transaction de transmission d'acte
// et être dans l'état Acquittement reçu
if ($trans->isType(TypeTransaction::TransmissionActe) && $transStatus == 4  && $userContext->me->checkDroit('actes', 'TT') && !  $userContext->me->isGroupAdminOrSuper()) {
    $actionHtml .= "<div class=\"action\">\n";
    if (!$trans->hasPendingCancelTrans()) {
            $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_cancel.php\" onsubmit=\"return confirm('Voulez-vous vraiment annuler cette transaction ?')\" method=\"post\">\n");
            $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Annulation : </label>\n";
            $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
            $actionHtml .= "<input type=\"submit\" value=\"Annuler cette transaction\" class=\"btn btn-danger\" />\n";
            $actionHtml .= "</div></form>\n";
    } else {
        $actionHtml .= "Une demande d'annulation est en cours pour cet acte.";
    }
    $actionHtml .= "</div>\n";
}

// Boutons de réponse à un courrier

if (in_array($transStatus, [7,8,21]) && !$trans->isType(TypeTransaction::DefereAuTribunalAdministratif)  && $userContext->me->checkDroit('actes', 'CS')) {
      $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_repondre.php\" method=\"post\">\n");
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Répondre : </label>\n";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Répondre à ce document\" class=\"btn btn-primary\" />\n";
      $actionHtml .= "</div></form>\n";
}

if ($transStatus == 17 && $userContext->me->checkDroit("actes", "TT")) {
      $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_post_confirm.php\" method=\"post\">\n");
      $actionHtml .= "<p>Valider &nbsp;:&nbsp;";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Poster ce document\" class=\"submit_button\" />\n";
      $actionHtml .= "</p></form>\n";
}

$actionHtml .= "<div class=\"action\">\n";
$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Horodatage : </label>\n<a onclick=\"window.open(this.href); return false;\" href=\"" .
    Helpers::getLink(
        "/common/logs_view.php?module=actes&amp;severity=-1&amp;message=" . $trans->getId() . "\" title=\"Rechercher les logs relatifs à l'acte n°" . $trans->getId()  . " \" >Rechercher les logs relatifs à l'acte</a>\n"
    );
$actionHtml .= "</div>\n</div>\n";

if ($userContext->me->isSuper()) {
       $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_delete.php\" onsubmit=\"return confirm('Cette transaction sera éradiquée DEFINITIVEMENT de la base sans espoir de retour?')\" method=\"post\">\n");
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Effacer de la base de donnée (TRES DANGEREUX) : </label>\n";
      $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
      $actionHtml .= "<input type=\"submit\" value=\"Effacer de la base de données\" class=\"btn btn-danger\" />\n";
      $actionHtml .= "</div></form>\n";

      $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_set_error.php\" onsubmit=\"return confirm('Cette transaction sera passée en erreur ')\" method=\"post\">\n");
      $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer la transaction en erreur </label>\n";
    $actionHtml .= "<input type=\"text\" placeholder='Message' name=\"message\"  size='23' style='margin-right: 10px;' />\n";
    $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
    $actionHtml .= "<input type=\"submit\" value=\"Passer en erreur\" class=\"btn btn-warning\" />\n";
    $actionHtml .= "</div></form>\n";



    if (in_array($transStatus, [3,-1])  && $trans->isType(TypeTransaction::TransmissionActe)  && $userContext->me->checkDroit("actes", "CS")) {
        $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_rolback_attente.php\" onsubmit=\"return confirm('Êtes-vous certain de vouloir faire cela ? ')\" method=\"post\">\n");
        $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer à En attente de transmission </label>\n";
        $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "<input type=\"submit\" value=\"Passer en attente de transmission\" class=\"btn btn-warning\" />\n";
        $actionHtml .= "</div></form>\n";

        $actionHtml .= "<form action=\"" . Helpers::getLink("/modules/actes/actes_transac_rolback_attente.php\" onsubmit=\"return confirm('Êtes-vous certain de vouloir faire cela ? ')\" method=\"post\">\n");
        $actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer à Poster </label>\n";
        $actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
        $actionHtml .= "<input type=\"hidden\" name=\"status_id\" value=\"1\" />\n";
        $actionHtml .= "<input type=\"submit\" value=\"Passer à Poster\" class=\"btn btn-warning\" />\n";
        $actionHtml .= "</div></form>\n";
    }
}

if (isset($actionHtml)) {
    $html .= "<h2>Actions</h2>\n";
    $html .= $actionHtml;
}


$html .= "</div>\n";


$doc->addBody($html);

$doc->closeContainer();

$doc->buildFooter();

$doc->display();

