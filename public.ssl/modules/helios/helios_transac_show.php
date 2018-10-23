<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');
require_once(SITEROOT . '/class/helios/HeliosSignature.class.php');

$module = new Module();
if (! $module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Éhec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = Helpers::getVarFromGet("id");



$myAuthority = new Authority($me->get("authority_id"));


$trans = new HeliosTransaction();

if (isset($id) && ! empty($id)) {
  $trans->setId($id);
  if ($trans->init()) {//obtine inregistrarea ce corespunde 
	$owner = new User($trans->get("user_id")); //!!!!! din HeliosTransaction
	$owner->init();
  } else {
	$_SESSION["error"] = "Erreur d'initialisation de la transaction.";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit();
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant de transaction spécifié";
  header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
  exit();
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$permission = new ModulePermission($serviceUser,"helios");

if ( ! $permission->canView($me,$owner)){
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/helios/index.php");
	exit ();
}

$doc = new HTMLLayout();

$doc->setTitle("Helios : visualisation de transactions pour un fichier");
$doc->addBody("<div class=\"container\"><div class=\"row\">");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$currentStatus=HeliosTransactionWorkflow::getCurrentStatus($id);

$user_id = $trans->get('user_id');
$userInfo = new User($user_id);
$userInfo->init();

$authorityInfo = new Authority($userInfo->get("authority_id"));
$authorityInfo->init();


$html = "<p id=\"back-transaction-btn\"><a href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"btn btn-default\">Retour liste transactions</a></p>\n";
$html .= "<h2>Visualisation de transactions d'un fichier</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data table table-bordered\">\n";
$html .= $doc->getHTMLArrayline("Fichier", $trans->getFilenameForID($id));
if ($trans->get('xml_nomfic')){
	$html .= $doc->getHTMLArrayline("Nom du fichier posté (balise NomFic)", $trans->get('xml_nomfic'));
	$html .= $doc->getHTMLArrayline("Code collectivité (codcol)", $trans->get('xml_cod_col'));
	$html .= $doc->getHTMLArrayline("Code budget (codbud)", $trans->get('xml_cod_bud'));
	$html .= $doc->getHTMLArrayline("Identifiant du poste comptable (idPost)", $trans->get('xml_id_post'));
}
$html .= $doc->getHTMLArrayline("Date de postage" ,Helpers :: getDateFromBDDDate(HeliosTransactionWorkflow::getDatePoste($id), true));
$html .= $doc->getHTMLArrayline("État actuel" ,$currentStatus);
$html .= $doc->getHTMLArrayline("Taille (octets)" ,$trans->get("file_size"));
$html .= $doc->getHTMLArrayline("Empreinte SHA1" ,$trans->get("sha1"));
$html .= $doc->getHTMLArrayline("Suivie par" ,$userInfo->getPrettyName());
$html .= $doc->getHTMLArrayline("Collectivité" ,$authorityInfo->get("name"));
$arch_url = $trans->get("archive_url");

if (!empty ($arch_url)) {
      $url = "<a href=\"" . $trans->get("archive_url") . "\">" . get_hecho($trans->get("archive_url")) . "</a>";
    } else {
      $url = "Non définie";
    }
$html .= $doc->getHTMLArrayline("URL d'archivage", $url);
$html .= "</table>\n";
$html .= "</div>\n";


$html .= "<h2>Récuperation du fichier posté ";
$html .= "<a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_file.php?id=" .$id. "\" title=\"Télécharger le fichier\">".$trans->getFilenameForID($id)."</a> </h2>";


if ($me->isSuper()) {
	$link = WEBSITE_SSL ."/modules/helios/helios_transac_validate_pes_aller.php?id=$id";
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

if ($trans->get('acquit_filename')){
	$html .= " <a href=\"" .WEBSITE_SSL. "/modules/helios/helios_download_acquit.php?id=" .$id. "\" title=\"Télécharger l'acquittement\">Télécharger le PES Acquit</a> ";
}


$currentStatusId = HeliosTransactionWorkflow::getCurrentStatusId($id);

$actionHtml = "";
if (in_array($currentStatusId,array(8,4,11,20))) {
	$actionHtml .= "<div class=\"action\">\n";
	$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/helios_transac_archiver.php\"  method=\"post\">\n";
	$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Archivage SEDA : </label>\n";
	$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $trans->getId() . "\" />\n";
	$actionHtml .= "<input type=\"submit\" class=\"btn btn-primary\" value=\"Versement manuel\" />\n";
	$actionHtml .= "</div>\n</form>\n";
}



if ($me->isSuper()) {

	$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/helios_transac_delete.php\" onsubmit=\"return confirm('Cette transaction sera éradiquée DEFINITIVEMENT de la base sans espoir de retour?')\" method=\"post\">\n";
	$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Effacer de la base de donnée (TRES DANGEREUX) : </label>\n";
	$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />\n";
	$actionHtml .= "<input type=\"submit\" value=\"Effacer de la base de données\" class=\"btn btn-danger\" />\n";
	$actionHtml .= "</div></form>\n";
	
	$actionHtml .= "<form action=\"" . WEBSITE_SSL . "/modules/helios/helios_transac_set_error.php\" onsubmit=\"return confirm('Cette transaction sera passée en erreur ')\" method=\"post\">\n";
	$actionHtml .= "<div class=\"form-group\">\n<label class=\"col-md-4 control-label\">Passer la transaction en erreur </label>\n";
	$actionHtml .= "<input type=\"hidden\" name=\"id\" value=\"" . $id. "\" />\n";
	$actionHtml .= "<input type=\"submit\" value=\"Passer la transaction en erreur\" class=\"btn btn-warning\" />";
	$actionHtml .= "&nbsp;&nbsp;Message d'erreur : <input type=\"text\" name=\"message\"  size='30' />\n";
	$actionHtml .= "</div></form>\n";

	if( in_array($currentStatusId, array(2,3,-1,7))){
		ob_start();?>

	<form
		action="<?php WEBSITE_SSL ?>/modules/helios/helios_transac_rollback.php"
		method="post"
		onsubmit="return confirm('Êtes-vous certain de vouloir faire cela ?')"
		>
		<div class="form-group">
			<label class="col-md-4 control-label">Repasser la transaction en « posté »</label>
			<input type="hidden" name="id" value="<?php hecho($id) ?>"/>
			<input type="submit" value="Revenir en arrière" class="btn btn-danger" />
		</div>


	</form>


		<?php
		$actionHtml.= ob_get_contents();
		ob_end_clean();

	}
}


if (isset($actionHtml)) {
	$html .= "<h2>Actions</h2>\n";
	$html .= $actionHtml;
}


if ($currentStatusId == 13 && $me->checkDroit($module->get("name"),'CS') ){
	$html .= "<h3>Signature du fichier PES</h3>";
	
	$heliosSignature = new HeliosSignature();
	try{
        $pesAllerRetriever = $objectInstancier->get("PesAllerRetriever");
        $pesaller_path = $pesAllerRetriever->getPath($trans->get('sha1'));
		$signatureInfo=$heliosSignature->getInfoForSignature($pesaller_path);
		$id_pes = $signatureInfo['bordereau_id'];

		ob_start();
		$libersignController = new LibersignController($objectInstancier);
		$libersignController->displayLibersignJS();

		?>

		<script>
			$(window).load(function() {

				$(document).ready(function () {

					$("#box_result").hide();

					var siginfos = [];

					siginfos.push({
						hash: "<?php echo $signatureInfo['bordereau_hash']?>",
						pesid: "<?php echo $signatureInfo['bordereau_id']?>",
                        pespolicyid: "urn:oid:1.2.250.1.131.1.5.18.21.1.7",
						pespolicydesc: "Politique de signature Helios de la DGFiP",
                        pespolicyhash: "roF9+cfRHNPtVJolhdqfIqGMVuUXX8aR4rpiquf0u5E=",
                        pesspuri: "https://www.collectivites-locales.gouv.fr/files/files/finances_locales/dematerialisation/ps_helios_dgfip.pdf",
						pescity: "<?php hecho($authorityInfo->get('city'))?>",
						pespostalcode: "<?php hecho($authorityInfo->get('postal_code'))?>",
						pescountryname: "France",
						pesclaimedrole: "Ordonnateur",
						pesencoding: "iso-8859-1",
                        format: "xades-env-1.2.2-sha256"
					});

					$(".libersign").libersign({
						iconType: "glyphicon",
						signatureInformations: siginfos
					}).on('libersign.sign', function (event, signatures) {
						//console.log(signatures);
						$("#signature_1").val(signatures[0]);
						$("#form_sign").submit();
					});
				});
			});
		</script>

		<div id='box_signature' class='box' style="width:920px" >
			<h2>Signature</h2>
			<div class="libersign"></div>
		</div>

<form action='<?php echo WEBSITE_SSL?>modules/helios/helios_transac_sign.php' id='form_sign' method='post'>
	<input type='hidden' name='id_1' id='form_sign_id' value='<?php echo $id ?>'/>
	<input type='hidden' name='nb_signature'  value='1'/>
	<input type='hidden' name='signature_id_1' value='<?php echo $id_pes?>' />
	<input type='hidden' name='signature_1' id='signature_1' value=''/>
	<input type='hidden' name='is_bordereau_1' id='is_bordereau_1' value='<?php echo $signatureInfo['isbordereau'] ?>'/>
	
</form>
	<?php
		$html.= ob_get_contents();
		ob_end_clean();
		
	} catch (Exception $e){
		$html.="<div class='alert alert-warning'><strong>Au moins un bordereau du fichier PES ne contient pas d'identifiant : la signature est impossible</strong></div>";
	}
}


if ($currentStatusId == 14 && $me->checkDroit($module->get("name"),'TT') ){

ob_start(); ?>
<h3>Télétransmission du fichier</h3>
<p>
<form action='<?php echo WEBSITE_SSL?>modules/helios/helios_transac_submit.php' id='form_sign' method='post'>
	<input type='hidden' name='id'  value='<?php echo $id?>'/>
	<input class='submit_button' type='submit' value='Télétransmettre'/>

</form>
</p>
<?php 	
		$html.= ob_get_contents();
		ob_end_clean();



}

$html .= "</div>\n";
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
