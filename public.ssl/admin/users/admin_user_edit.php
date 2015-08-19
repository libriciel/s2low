<?php

require_once(__DIR__."/../../../init/init.php");


$x509Certificate = new X509Certificate();

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accés refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

$myAuthority = new Authority($me->get("authority_id"));

// Mode modification ou pas
$mod = false;
$him = new User();

if (isset($id) && ! empty($id)) {
  $him->setId($id);
  if ($him->init()) {    
    $mod = true;
  } else {
    $him = new User();
  }
}

$new_id = Helpers::getVarFromGet('new_id'); 
if ($new_id){
	$him->setId($new_id);
	$him->init();
	$him->setId(null);
	$him->set("login","");
	$mod = true;
}

if (! $me->isSuper() && $mod) {
	if ($id) {
		$canUserEdit = $me->canEditUser($id);
	} else {
		$canUserEdit = $me->canEditUser($new_id);
	}
  if (! $canUserEdit) {
	$_SESSION["error"] = "Impossible de modifier cet utilisateur. Accés refusé.";
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	exit();
  }
}



if ($mod){
	$title = "Modification de l'utilisateur « {$him->get('givenname')} {$him->get('name')} »";
} else {
	$title = "Ajout d'un nouvel utilisateur";
}

//WTF !
$validate_form = $him->getValidationTrio('name', 'givenname', 'email', 'authority_id', 'role', 'status');
if (! $mod) {
	$validate_form .= ", 'certificate', 'Certificat utilisateur', 'RisString'";
}

$him_status = ($val = Helpers::getFromSession("status")) ? $val : $him->get("status");

if ($me->isGroupAdminOrSuper()) {
	if ($me->isGroupAdmin()) {
		//WTF !!
		$cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
	} else {
		$cond = " ORDER BY authorities.name ASC";
	}
	$authorities_list = Authority::getAuthoritiesIdName($cond);
	
	$him_authorities = ($val = Helpers::getFromSession("authority_id")) ? $val : $him->get("authority_id");
}

$him_role = ($val = Helpers::getFromSession("role")) ? $val : $him->get("role");



$roles_list = $me->get("roleTypes");
if (! $me->isSuper()) {
	// Les admin simple et de groupe ne peut pas créer un super admin ni un admin de groupe
	$tmp = array();

	foreach ($roles_list as $role => $descr) {
		if ($role != 'SADM' && $role != 'GADM') {
			$tmp[$role] = $descr;
		}
	}
	$roles_list = $tmp;
}

$groups_list = Group::getGroupsIdName();


// Récupération des modules actifs globalement
$modules = Module::getActiveModulesList();

// Récupération des modules authorisés pour la collectivité
$authModules = array();
if ($mod) {
	$authModules = Module::getModulesForAuthority($him->get("authority_id"));
} else {
	if ($me->isGroupAdminOrSuper()) {
		// On ne sait pas à l'avance à quelle collectivité appartiendra l'utilisateur
		foreach ($modules as $module) {
	  if ($me->isGroupAdmin()) {
	  	if ($me->canGrantModule($module["name"])) {
	  		$authModules[$module["id"]] = true;
	  	}
	  } else {
	  	$authModules[$module["id"]] = true;
	  }
		}
	} else {
		$authModules = $myAuthority->getAuthorizedModules();
	}
}

$certitificate_id_list = $him->getIdFromCertData($him->get("subject_dn"),$him->get("issuer_dn"));

$status_type_list = $me->get("statusTypes");
$roles_type_list = $me->get("roleTypes");

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$services_list = $serviceUser->getServiceUser($him->get('authority_id'));
if ($him->getId()){
	$userService_list = $serviceUser->getServiceFromUser($him->getId());
}

$userSQL = new UserSQL($sqlQuery);
$ident_method_id = $userSQL->getIdentificationMethod($him->getId());
$ident_method_libelle = $userSQL->getIdentificationMethodeLibelle($ident_method_id);

$certificat_rgs_2_etoiles_info = $x509Certificate->getInfo($him->get('certificate_rgs_2_etoiles'));


$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . WEBSITE_SSL . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("$title | Tedetis");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>
<h1><?php echo($title)?></h1>
<p id="back-user-btn"><a class="btn btn-default" href="admin_users.php">Retour liste utilisateurs</a></p>

<h2>Informations générales</h2>
<form class="form form-horizontal" 
		action="admin_user_edit_handler.php" 
		method="post" name="form" 
		enctype="multipart/form-data"  
		onsubmit="javascript:return validateForm(<?php echo  $validate_form ?>)"
		>
<?php if ($mod)  : ?>
	<?php if ($new_id) : ?>
		<input type="hidden" name="new_id" value="<?php echo $new_id ?>" />
		<input type="hidden" name="mode" value="new_id" />
	<?php else: ?>
  		<input type="hidden" name="id" value="<?php echo $him->getId() ?>" />
  		<input type="hidden" name="mode" value="modify" />
	<?php  endif; ?>
<?php else:?>
  <input type="hidden" name="mode" value="create" />
<?php endif;?>
		
		
<?php foreach(array('name'=>'Nom', 'givenname'=>"Prénom",'email'=>"Adresse électronique","telephone"=>"Téléphone") as $input_id => $input_label): ?>
<div class="form-group">
	<label class="control-label col-md-4"><?php echo $input_label?> : </label>
	<div class="col-md-6">
		<input class="form-control" type="text" name="<?php echo $input_id ?>" value="<?php echo ($val = Helpers::getFromSession($input_id)) ? get_hecho($val) : get_hecho($him->get($input_id)); ?>" size="30" maxlength="60" />
	</div>
</div>	
<?php endforeach;?>

<h2>Certification de connexion</h2>

<div class="form-group">
	<label class="control-label col-md-4">Importer le certificat utilisateur (format PEM) :</label>
	<div class="col-md-6">
		<input type="file" name="certificate" />
	</div>
</div>
<?php if ($him->get('certificate')) : ?>
	<div class="alert alert-info col-md-9 col-md-offset-1"> 
		<?php hecho($him->get('subject_dn')) ?>
		<br/>
		Expire le <?php echo date("d/m/Y H:i:s",strtotime($x509Certificate->getExpirationDate($him->get('certificate')))); ?>
	</div>
<?php endif;?>

<div style='clear:both'></div>
<h2>Méthode d'identification</h2>

<div class="form-group">
	<label class="control-label col-md-4">Méthode actuelle : </label>
	<div class="col-md-6">
		<?php hecho($ident_method_libelle)?>
	</div>
</div>	


<?php $input_label = "Login"; $input_id="login"?>
<div class="form-group">
	<label class="control-label col-md-4"><?php echo $input_label?> : </label>
	<div class="col-md-6">
		<input class="form-control" type="text" name="<?php echo $input_id ?>" value="<?php echo ($val = Helpers::getFromSession($input_id)) ? get_hecho($val) : get_hecho($him->get($input_id)); ?>" size="30" maxlength="60" />
	</div>
</div>	
	
<?php foreach(array('password'=>'Mot de passe', 'password2'=>"Mot de passe (à nouveau)") as $input_id => $input_label): ?>
<div class="form-group">
	<label class="control-label col-md-4"><?php echo $input_label?>: </label>
	<div class="col-md-6">
		<input class="form-control" type="password" name="<?php echo $input_id ?>" value="" size="30" maxlength="60" />
	</div>
</div>	
<?php endforeach;?>

<div class="form-group">
	<label class="control-label col-md-4">Certificat RGS** (format PEM) :</label>
	<div class="col-md-6">
		<?php if ($certificat_rgs_2_etoiles_info): ?>
			<?php hecho($certificat_rgs_2_etoiles_info['name']) ?><br/>
			Expire le : <?php echo $certificat_rgs_2_etoiles_info['expiration_date'] ?> - 
			<a href='admin_user_delete_certificat_rgs_2_etoiles.php?id=<?php echo $him->getId()?>'>Supprimer</a>
			
			<br/><br/>
		<?php endif;?>
		
		<input type="file" name="certificate_rgs_2_etoiles" />
	</div>
</div>


<h2>Droits</h2>

<div class="form-group">
	<label class="control-label col-md-4">État :</label>
	<div class="col-md-6 ">
	<?php echo $doc->getHTMLSelect("status", $status_type_list, $him_status); ?>
	</div>
</div>

<?php if ($me->isGroupAdminOrSuper()) :?>
	<div class="form-group">
  		<label class="control-label col-md-4">Collectivité :</label>
  		<div class="col-md-6">
  			<?php if (! $mod || $new_id) : ?>
  				<?php echo $doc->getHTMLSelect("authority_id", $authorities_list, $him_authorities); ?>
  			<?php else: ?>
				<?php hecho($authorities_list[$him->get("authority_id")]); ?>
			<?php endif;?>
		</div>
	</div>
<?php endif;?>

<div class="form-group">
	<label class="control-label col-md-4">Rôle :</label>
	<div class="col-md-6">
		<?php echo $doc->getHTMLSelect("role", $roles_list, $him_role); ?>
	</div>
</div>

<?php if ($me->isSuper()) : ?>
 	<div class="form-group">
  		<label class="control-label col-md-4">Groupe (pour un administrateur de groupe) :</label>
  		<div class="col-md-6">
		  	<?php echo $doc->getHTMLSelect("authority_group_id", $groups_list, $him->get("authority_group_id")); ?>
  		</div>
	</div>
<?php endif;?>



<?php foreach ($modules as $module): ?>
	<?php if ($me->isSuper() || $authModules[$module["id"]]) : ?>
		<?php 
		$class = "";
		if ($me->isSuper() && ! isset($authModules[$module["id"]])) {
			$class = " class=\"inactive\"";
		}
		?>
		<div class="form-group">
			<label class="control-label col-md-4 <?php echo $class ?>"><?php echo $module["description"] ?> :</label>
			<div class="col-md-6  <?php echo $class ?>">
				 <?php echo $doc->getHTMLSelect("perm_" . $module["id"], $me->getPermTypes($module['specific_perms']), $him->getPerm($module["name"])) ?>
			</div>
		</div>
	<?php endif;?>
<?php endforeach;?>



<div class="form-group">
	<button type="submit" class="col-md-offset-4 col-md-6 btn btn-default">
		<?php echo ($mod) ? "Valider les modifications" : "Ajouter l'utilisateur"; ?>
	</button>
</div>
	
</form>

<h2>Autre utilisateur partageant le même certificat</h2>
<?php if (count($certitificate_id_list) > 1) : ?>
	<div class="data_table">
		<table class="data-table table table-striped">
			<tr>
				<th class="data">Login</th>
				<th class="data">Nom</th>
				<th class="data">Adresse électronique</th>
				<th class="data">R&ocirc;le</th>
				<th class="data">État</th>
				<th class="data">Collectivit&eacute;</th>
				<th class="data">Actions</th>
			</tr>
			<?php foreach($certitificate_id_list as $i => $id_other):
					if ($id_other == $him->getId()){
						continue;
					}
					$he = new User($id_other);
					$he->init();
					$he_authority = new Authority($he->get("authority_id"));  ?>
	 				<tr class="alternate<?php echo (($i%2) + 1) ?>">
						<td><?php echo $he->get("login") ?></td> 		
						<td><?php echo $he->get("givenname") . " " . $he->get("name") ?></td>
						<td><a href="mailto: <?php echo $he->get("email") ?>"><?php echo $he->get("email") ?></a></td>
						<td><?php echo $roles_type_list[$he->get("role")] ?></td>
						<td><?php echo $status_type_list[$he->get("status")] ?></td>
						<td><a href="<?php echo WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $he->get("authority_id") ?>"><?php echo $he_authority->get("name")  ?></a></td>
						<td>
							<a href="<?php echo WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" .  $he->get("id") ?>" class="icon">
								<img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" alt="image_modif" title="Modifier" />
							</a>
						</td>
					</tr>
			<?php endforeach;?>
		</table>
	</div>
		
	<?php if ($him->get('login')) : ?>
		<a href='admin_user_edit.php?new_id=<?php echo ($id?$id:$new_id) ?>'>Créer un nouvel utilisateur avec le même certificat </a>
		<?php if ($him->get('subject_dn')) : ?>
			<?php echo $him->get('subject_dn') ?>	
		<?php endif; ?>
	<?php else: ?>
		Si vous voulez créer un autre utilisateur a partir du même certificat, vous devez saisir le champ login
	<?php endif;?>
<?php endif;?>

<?php if ($id && $services_list) : ?>
	<h2>Services</h2>
	<?php if ($userService_list) : ?>
		Cet utilisateur fait partie des services 
		<?php foreach($userService_list as $i => $s): ?>
			<?php if ($i != 0): ?>
				, 
			<?php endif; ?>
			<a href='../services/gestion-service-content.php?id=<?php echo $s['id'] ?>'><?php hecho($s['name']) ?></a>
		<?php endforeach;?>
		
	<?php else:  ?>
		Cet utilisateur ne fait partie d'aucun service
	<?php endif ?>
	<br/><br/>
	
	<form class="form form-horizontal" action='add-user-to-service.php' method='post'>
		<input type='hidden' name='id_user' value='<?php echo $him->getId() ?>' />
		<div class="form-group">
			<label class="col-md-3 control-label"> 
				Mettre dans le service : 
			</label>
			<div class="col-md-3">
				<select class="form-control" name='id_service'>
					<?php foreach($services_list as $s) : ?>
						<option value='<?php echo $s['id'] ?>'><?php hecho($s['name']) ?></option>	
					<?php endforeach; ?>
				</select>
			</div>\
			<input class="btn btn-primary btn-sm col-md-2" type='submit' value='Ajouter'>
			</div>
	</form>
	
<?php endif;?>


<?php 			
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
