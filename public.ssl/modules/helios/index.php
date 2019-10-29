<?php

require_once( __DIR__ . "/../../../init/init-www-helios.php");

require_once (SITEROOT . '/class/helios/HeliosTransactionsListe.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

$recuperateur = new Recuperateur($_GET);


$sortWay =   $recuperateur->get("sortway","desc");
$order = $recuperateur->get('order','id');
$page_number = $recuperateur->getInt('page',1);
$taille_page =  $recuperateur->getInt('count',10);


$fmin_submission_date =  $recuperateur->get("min_submission_date");
$fmax_submission_date =  $recuperateur->get("max_submission_date");
$fmin_ack_date = $recuperateur->get("min_ack_date");
$fmax_ack_date =  $recuperateur->get("max_ack_date");


if (isset( $_GET['status']) && $_GET['status'] === '0'){
	$fstatus = 0;
} else {
	$fstatus =  $recuperateur->get("status",'all');
}

$fauthority = $recuperateur->get("authority");
$fnum =  $recuperateur->get("num");
$fnomFic = $recuperateur->get("nomFic");


$heliosTransactionsListe = new HeliosTransactionsListe($sqlQuery);


if ($droit->isSuperAdmin($userInfo) ) {
	$heliosTransactionsListe->setAuthority($fauthority);
}elseif ($droit->isGroupAdmin($userInfo) && $fauthority){
	$authorityFiltreInfo = $authoritySQL->getInfo($fauthority);

	if ($droit->hasDroit($userInfo,$authorityFiltreInfo)){
		$heliosTransactionsListe->setAuthority($fauthority);
	} else {
		$heliosTransactionsListe->setAuthority($userInfo['authority_id']);
	}
}elseif ($droit->isAdmin($userInfo)){
	$heliosTransactionsListe->setAuthority($userInfo['authority_id']);
} else {
	$serviceUser = new ServiceUser(DatabasePool::getInstance());
	$collegues = $serviceUser->getMesCollegues($connexion->getId());
	$collegue[] = $connexion->getId();
	foreach($collegues as $info){
		$collegue[] =  $info['id_user'];
	}
	$heliosTransactionsListe->setUserId($collegue);
}
$heliosTransactionsListe->setOrder($order,$sortWay);
$heliosTransactionsListe->setPageNumber($page_number,$taille_page);
$heliosTransactionsListe->setDateMinSubmission($fmin_submission_date);
$heliosTransactionsListe->setDateMaxSubmission($fmax_submission_date);
$heliosTransactionsListe->setDateMinAck($fmin_ack_date);
$heliosTransactionsListe->setDateMaxAck($fmax_ack_date);
$heliosTransactionsListe->setStatus($fstatus);
$heliosTransactionsListe->setObjet($fnum);
$heliosTransactionsListe->setNomFic($fnomFic);


//if ($droit->isSuperAdmin($userInfo)) {
//On empeche le comptage des transactions pour tout le monde (c'est trop long)
	$nb_transactions = ($page_number+10)*$taille_page;
/*} else {
	$nb_transactions = $heliosTransactionsListe->getNbTransaction();
}*/



// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Echec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accés refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}


$helios_configured = $authorityInfo["helios_ftp_dest"];


$envelopes = $heliosTransactionsListe->getAll();


$js =<<<EOJS
<script type="text/javascript">
//<![CDATA[
function show_all() {
  toggle_all("block", "-");
}

function hide_all() {
  toggle_all("none", "+");
}

function toggle_all(style, symbol) {
  done = false;
  i = 0;

  while (! done) {
	var content = document.getElementById("envelope_content_" + i);
	var expander = document.getElementById("expander_" + i);

	if (content && expander) {
	  content.style.display = style;
	  expander.innerHTML = symbol;
	} else {
	  done = true;
	}
	i++;
  }
}

function toggle_envelope_content(id) {
  var content = document.getElementById("envelope_content_" + id);
  var expander = document.getElementById("expander_" + id);

  if (content.style.display == "block") {
	content.style.display = "none";
	expander.innerHTML = "+";
  } else {
	content.style.display = "block";
	expander.innerHTML = "-";
  }  
}

function GereChkbox(conteneur, a_faire) {
  var blnEtat=null;
  var Tab = document.getElementsByTagName("input");
  for(var i = 0; i < Tab.length; i++){  
	Chckbox=Tab[i];
	if (Chckbox.getAttribute("type")=="checkbox") {
		blnEtat = (a_faire=='0') ? false : (a_faire=='1') ? true : (Chckbox.checked) ? false : true;
		Chckbox.checked=blnEtat;
	}
  }
}

function afficheWarning(){
  var n = 0;
  var liste = document.getElementsByTagName("input");
  for(var i = 0; i < liste.length; i++){
    if (liste[i].getAttribute("type")=="checkbox"){
      if(liste[i].checked) n++;
    }
  }
  var msg = "Voulez-vous vraiment affecter les " + n + " transactions sélectionnées ? ";
  msg += "Cette action est non réversible et est sous votre entière responsabilité";
  return confirm(msg);
}
//]]>
</script>
EOJS;


$menuHTML = new MenuHTML();
$pagerHTML  = new PagerHTML();

$doc = new HTMLLayout();
$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />");

$doc->setTitle("Tedetis : module helios");

$doc->openContainer();

$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($userInfo,$modulesInfo));
$doc->addBody($pagerHTML->getHTML($page_number,$nb_transactions,$taille_page));

$doc->closeSideBar();


$doc->openContent();


$status = HeliosTransaction :: getStatusList();
$status["999"] = "En cours";
$status["all"] = "Tous les états";

if ($envelopes){
	$owner = new User($envelopes[0]["user_id"]);
	$owner->init();
}

$sortWay = (isset($_GET['sortway']) && ($_GET["sortway"] == "asc")) ? "desc" : "asc";
$sel_ok = array();


ob_start();
?>
<script type="text/javascript" src="/javascript/jfu/js/jquery.min.js"></script>
<script type="text/javascript" src="/javascript/zselect.js"></script>
<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>

<h1>Helios - Dématérialisation de documents financiers</h1>


<div id="actions_area">
	<h2>Actions</h2>

	<?php if(! $helios_configured) : ?>
		<div class='alert alert-danger'>Attention les paramètres du module Helios sont incomplets et ne permettront pas la télétransmission.</div>
	<?php endif; ?>

	<?php if (!$me->isSuper() && $me->canEdit($module->get('name'))) : ?>
		<?php if ($module->getParam("paper") == "on") : ?>
			<p>Le système est actuellement en mode &nbsp;papier&nbsp;.
				Dans ce mode il est impossible de créer de nouvelle transaction.
				Les transferts doivent se faire par les moyens classiques (non dématérialisé).
			</p>
		<?php else :  ?>
	 		<a class="btn btn-primary" href="<?php echo WEBSITE_SSL ?>/modules/helios/helios_fichier_import.php" >Importer un fichier</a>
		<?php endif; ?>
	<?php endif; ?>
	<a class="btn btn-primary" href="<?php echo WEBSITE_SSL ?>/modules/helios/helios_retour.php" title="afficher la liste des réponses reçues">Réponse d'Hélios</a>
</div>

<h2 class="toggle_title" onclick="javascript:toggle_visibility('filtering-area');">Filtrage</h2>
<div id="filtering-area">
	<form  role="form" class="form-horizontal" action="<?php echo WEBSITE_SSL ?>/modules/helios/index.php" method="get">
		<div class="form-group">
			<label class="col-md-3 control-label" for="status">État</label>
			<div class="col-md-3">
				<?php echo $doc->getHTMLSelect("status", $status, $fstatus)  ?>
			</div>
			<label class="col-md-3 control-label" for="filename-contain">Le nom de fichier contient</label>
			<div class="col-md-3">
				<input id="filename-contain" class="form-control" type="text" name="num" size="20" maxlength="25"
					value="<?php echo (strlen($fnum) > 0)?$fnum:"" ?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3" for="min_submission_date">Date de postage minimale</label>
			<div class="col-md-3">
				<input id="min_submission_date" name="min_submission_date" type="hidden" value="<?php hecho($fmin_submission_date) ?>"/>
				<script type="text/javascript">
					obj_min_submission_date = new DatePicker('min_submission_date', 'fr');
				</script>
				<a href="#datepicker" id="datepicker_min_submission_date_link" class="datepicker_link" onclick="javascript:obj_min_submission_date.toggleDatePicker(); return false">
					<?php if ($fmin_submission_date)  : ?>
						<?php echo utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmin_submission_date))); ?>
					<?php else : ?>
						Choisir une date
					<?php endif; ?>
				</a>
				<div class="date_picker" style="display: none;" id="datepicker_min_submission_date_calendar"></div>
			</div>
			<label class="col-md-3" for="min_ack_date">Date d'acquittement minimale</label>
			<div class="col-md-3">
				<input id="min_ack_date" name="min_ack_date" type="hidden" value="<?php hecho($fmin_ack_date) ?>" />
				<script type="text/javascript">
					obj_min_ack_date = new DatePicker('min_ack_date', 'fr');
				</script>
				<a href="#datepicker" id="datepicker_min_ack_date_link" class="datepicker_link" onclick="javascript:obj_min_ack_date.toggleDatePicker(); return false;">
					<?php if ($fmin_ack_date): ?>
					  <?php echo utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmin_ack_date))); ?>
					<?php else : ?>
					  Choisir une date
					<?php endif; ?>
				</a>
				<div class="date_picker" style="display: none;" id="datepicker_min_ack_date_calendar">
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3" for="max_submission_date">Date de postage maximale</label>
			<div class="col-md-3">
				<input id="max_submission_date" name="max_submission_date" type="hidden" value="<?php hecho($fmax_submission_date) ?>"/>
				<script type="text/javascript">
					obj_max_submission_date = new DatePicker('max_submission_date', 'fr');
				</script>
				<a href="#datepicker" id="datepicker_max_submission_date_link" class="datepicker_link" onclick="javascript:obj_max_submission_date.toggleDatePicker(); return false;">
					<?php if ($fmax_submission_date) :?>
  						<?php echo utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmax_submission_date))); ?>
					<?php else: ?>
  						Choisir une date
  					<?php endif; ?>
				</a>
				<div class="date_picker" style="display: none;" id="datepicker_max_submission_date_calendar">
				</div>
			</div>
			<label class="col-md-3" for="max_ack_date">Date d'acquittement maximale</label>
			<div class="col-md-3">
				<input id="max_ack_date" name="max_ack_date" type="hidden" value="<?php hecho($fmax_ack_date) ?>" />
				<script type="text/javascript">
					obj_max_ack_date = new DatePicker('max_ack_date', 'fr');
				</script>
				<a href="#datepicker" id="datepicker_max_ack_date_link" class="datepicker_link" onclick="javascript:obj_max_ack_date.toggleDatePicker(); return false;">
					<?php if ($fmax_ack_date) :?>
					  <?php echo utf8_decode(strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($fmax_ack_date))); ?>
					<?php else : ?>
					  Choisir une date
					<?php endif; ?>
				</a>
				<div class="date_picker" style="display: none;" id="datepicker_max_ack_date_calendar">
				</div>
			</div>
		</div>
		<div class="form-group">

			<?php if ($me->isGroupAdminOrSuper()) : ?>
				<label for="authority" class="col-md-3 control-label">Collectivité</label>
				<div class="col-md-3">
					<select class="form-control zselect_authorities" name="authority">
						<?php if($me->isSuper()) : ?><option value="">Toutes</option><?php endif; ?>
                        <?php foreach ( $me->getAllPossibleAuthority() as $key => $val) : ?>
							<option value="<?php hecho($key) ?>"  <?php echo (strcmp($key, $fauthority) == 0) ? " selected='selected'" : ""; ?>>
								<?php hecho($val)?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif;?>
			<label class="col-md-3 control-label" for="xml-nomfic-contain">La balise NomFic contient</label>
			<div class="col-md-3">
				<input id="xml-nomfic-contain" class="form-control" type="text" name="nomFic" size="20"
					value="<?php echo (strlen($fnomFic) > 0)?$fnomFic:"" ?>" />
			</div>

		</div>
		<div class="form-group">
			<button type="submit" class="col-md-offset-3 col-md-3 btn btn-default">Filtrer</button>
			<a href="<?php WEBSITE_SSL ?>/modules/helios/index.php" class="col-md-offset-3 col-md-3 btn btn-default">Remise à zéro</a>
		</div>
	</form>
</div>

<h2>Liste des fichiers postés</h2>
<div id="transaction-area">

<?php if (count($envelopes) <= 0) : ?>
	Pas de transaction trouvée correspondant aux critères de filtrage.
<?php else: ?>

	<form id="div_chck" onsubmit="return afficheWarning();" action="<?php echo WEBSITE_SSL ?>/modules/helios/helios_transac_close.php" method="post">
		<table class="transactions_list">
			<table id="transaction-list" class="data-table table table-striped" summary="Ce tableau présente respectivement le nom de fichier, la date, le statut, l'auteur et un lien vers les actions disponibles de chaque fichier Helios posté">
				<caption>Liste des fichiers Helios postés en fonction des choix de filtrage</caption>
				<thead>
					<tr>
						<th>Sél.</th>
						<th id="filename">Nom de fichier</th>
						<th id="date">Date de postage</th>
						<th id="status">Etat actuel</th>
						<th id="authority-name">Suivie par</th>
						<?php if ($me->isGroupAdminOrSuper()) : ?>
							<th>Collectivité</th>
						<?php endif; ?>
						<th id="action">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php  foreach ($envelopes as $envelope) : ?>
						<tr>
							<td>
							<?php if (in_array($envelope['last_status_id'],array(4,8,13,11,20))) : ?>
								<input type="checkbox" name="liste_id[]" value="<?php hecho($envelope['id']) ?>" id="checkbox<?php echo $envelope['id'] ?>" />
								<?php $sel_ok[$envelope['last_status_id']] = true ?>
							<?php else : ?>
								&nbsp;
							<?php endif; ?>
							</td>
							<td headers="filename">
								<?php hecho($envelope["filename"]); ?>
								<?php if ($envelope['xml_nomfic']) : ?>
									<br/><small> NomFic : <?php echo $envelope['xml_nomfic'] ?></small>
								<?php endif; ?>
							</td>
							<td headers="date">
								<?php echo Helpers::getDateFromBDDDate($envelope['submission_date'], true) ?>
							</td>
							<td headers="status">
								<?php hecho($envelope['message']) ?>
							</td>
							<td headers="authority-name">
								<?php hecho($envelope['givenname'] ." ". $envelope['name'])?>
							</td>
							<?php if ($me->isGroupAdminOrSuper()) : ?>
								<td><?php hecho($envelope['authority_name']) ?></td>
							<?php endif; ?>
							<td headers="action">
								<a href="<?php echo WEBSITE_SSL ?>/modules/helios/helios_transac_show.php?id=<?php echo $envelope["id"] ?>" class="icon">
									<img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" alt="image_modif" title="Afficher le détail" />
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
		<br/><br/>
		<div id="selection-actions">
			<a href="#tedetis" onclick="GereChkbox('div_chck','1');" title="Tout sélectionner" class="btn btn-default">Tout sélectionner</a>
			<a href="#tedetis" onclick="GereChkbox('div_chck','0');" title="Tout désélectionner" class="btn btn-default">Tout desélectionner</a>
			<a href="#tedetis" onclick="GereChkbox('div_chck','2');" title="Inverser la sélection" class="btn btn-default">Inverser la sélection</a>
		</div>
		<br/>

		<?php if (isset($sel_ok[4]) || isset($sel_ok[8]) ||  isset($sel_ok[11]) || isset($sel_ok[20])) : ?>
			<input type='submit' class='btn btn-default' value='Envoyer la sélection au SAE'/>
		<?php endif; ?>
	</form>


<?php endif; ?>



	<?php   if (isset($sel_ok[13])) : ?>
		<form id='form-sign' action="<?php echo WEBSITE_SSL ?>/modules/helios/helios_batch_sign.php" method="post">
			<input id='signer_button' type='submit' class='btn btn-default' value="Signer les transactions sélectionnées">
		</form>
		<script type='text/javascript'>
			$(document).ready(function() {
				$("#signer_button").click(function(){
					$("input:checkbox:checked").each(function() {
						$("#form-sign").append("<input type='hidden' name='liste_id[]' value='" + $(this).val() + "' />");
					});
					$("#form-sign").submit();
					return false;
				})
			});
		</script>
	<?php endif; ?>
</div>
<?php

$html = ob_get_contents();
ob_end_clean();
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();
$doc->buildFooter();
$doc->display();
