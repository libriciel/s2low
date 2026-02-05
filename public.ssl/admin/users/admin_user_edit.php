<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\RgsCertificate;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

list($objectInstancier, $jsonOutput,$sqlQuery, $frontController,$pathToValidCa) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [ObjectInstancier::class, JSONoutput::class, SQLQuery::class, FrontController::class,'app.path_to_rgs_valid_ca']
    );

$html = '';
$x509Certificate = new X509Certificate();

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (! $me->isAdmin()) {
    $_SESSION["error"] = "Accés refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

if (!ctype_digit($id) && !(is_null($id) || !$id)) {
    $_SESSION["error"] = "id n'est pas un entier";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$myAuthority = new Authority($me->get("authority_id"));

// Mode modification ou pas
$mod = false;
$him = new User();

if (isset($id) && ! empty($id)) {
    if (!is_numeric($id)) {
        Helpers :: returnAndExit(
            1,
            "admin_user_edit.php : id doit être un entier, $id fourni",
            WEBSITE_SSL
        );
    }
    $him->setId($id);
    if ($him->init()) {
        $mod = true;
    } else {
        $him = new User();
    }
}



$new_id = Helpers::getVarFromGet('new_id');
if ($new_id) {
    if (!is_numeric($new_id)) {
        Helpers :: returnAndExit(
            1,
            "admin_user_edit.php : new_id doit être un entier, $new_id fourni",
            WEBSITE_SSL
        );
    }
    $him->setId($new_id);
    $him->init();
    $him->setId(null);
    $him->set("login", "");
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
        header("Location: " . Helpers::getLink("/admin/users/admin_users.php"));
        exit();
    }
}



if ($mod) {
    if ($new_id) {
        $title = sprintf(
            "Ajout d'un nouvel utilisateur (à partir de « %s %s »)",
            $him->get('givenname'),
            $him->get('name')
        );
    } else {
        $title = sprintf(
            "Modification de l'utilisateur « %s %s  »",
            $him->get('givenname'),
            $him->get('name')
        );
    }
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
        $cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id") . " ORDER BY authorities.name ASC";
    } else {
        $cond = " ORDER BY authorities.name ASC";
    }
    $authorities_list = Authority::getAuthoritiesIdName($cond);

    $him_authorities = ($val = Helpers::getFromSession("authority_id")) ? $val : $him->get("authority_id");
}

$him_role = ($val = Helpers::getFromSession("role")) ? $val : $him->get("role");



$roles_list = $me->getAvailableRolesForUserCreation();

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


$certitificate_id_list = $him->getIdFromCertData($him->get("certificate_hash")) ?: [];


$status_type_list = $me->get("statusTypes");
$roles_type_list = User::ROLES_DESCR;

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$services_list = $serviceUser->getServiceUser($him->get('authority_id'));
$userService_list = [];
if ($him->getId()) {
    $userService_list = $serviceUser->getServiceFromUser($him->getId());
}

$userSQL = new UserSQL($sqlQuery);

$ident_method_id = $userSQL->getIdentificationMethod($him->getId() ?: $new_id);
$ident_method_libelle = $userSQL->getIdentificationMethodeLibelle($ident_method_id);

$certificate_rgs_2_etoiles = $him->get('certificate_rgs_2_etoiles');

$certificat_rgs_2_etoiles_info = $x509Certificate->getInfo($certificate_rgs_2_etoiles);


$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . Helpers::getLink("/javascript/validateform.js\" type=\"text/javascript\"></script>\n"));

$doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/jquery.js") . '"></script>');
$doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/select2.js") . '"></script>');
$doc->addHeader('<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>');


$doc->setTitle("$title | Tedetis");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>
<h1><?php hecho($title)?></h1>
<p id="back-user-btn"><a class="btn btn-default" href="admin_users.php">Retour liste utilisateurs</a></p>

<h2>Informations générales</h2>
<form class="form form-horizontal" 
        action="admin_user_edit_handler.php" 
        method="post" name="form" 
        enctype="multipart/form-data"  
        onsubmit="javascript:return validateForm(<?php echo  $validate_form ?>)"
        >
<?php if ($mod) : ?>
    <?php if ($new_id) : ?>
        <input type="hidden" name="new_id" value="<?php echo $new_id ?>" />
        <input type="hidden" name="mode" value="new_id" />
    <?php else : ?>
        <input type="hidden" name="id" value="<?php echo $him->getId() ?>" />
        <input type="hidden" name="mode" value="modify" />
    <?php  endif; ?>
<?php else :?>
  <input type="hidden" name="mode" value="create" />
<?php endif;?>
        
        
<?php foreach (array('name' => 'Nom', 'givenname' => "Prénom",'email' => "Adresse électronique","telephone" => "Téléphone") as $input_id => $input_label) : ?>
<div class="form-group">
    <label class="control-label col-md-4"><?php echo $input_label?> : </label>
    <div class="col-md-6">
        <input class="form-control" type="text" name="<?php echo $input_id ?>" value="<?php echo ($val = Helpers::getFromSession($input_id)) ? get_hecho($val) : get_hecho($him->get($input_id)); ?>" size="30" maxlength="60" />
    </div>
</div>  
<?php endforeach;?>

<h2>Authentification</h2>
<div class="form-group">
    <label class="control-label col-md-4">Méthode : </label>
    <div class="col-md-6">
        <select id="auth_method" name="auth_method">
            <?php foreach ($userSQL->getIdentificatonMethodeList() as $ident_id => $ident_libelle) : ?>
                <option value="<?php echo $ident_id ?>" <?php echo $ident_method_id == $ident_id ? 'selected="selected"' : '' ?>>
                    <?php echo $ident_libelle ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<br/><br/>
    <script>

    function resetFormElement(e) {
        e.wrap('<form>').closest('form').get(0).reset();
        e.unwrap();

    }

    function setFormAuth(){
        $("#login-form").hide();
        $("#rgs2-form").hide();
        if($("#auth_method").val()==1){
            $("#password").val("");
            $("#login").val("");
            $("#password2").val("");
            resetFormElement($("#certificate_rgs_2_etoiles"));
        }
        if($("#auth_method").val()==2){
            $("#login-form").show();
            resetFormElement($("#certificate_rgs_2_etoiles"));
        }
        if($("#auth_method").val()==3){
            $("#rgs2-form").show();
            $("#password").val("");
            $("#login").val("");
            $("#password2").val("");
        }
    }

    $( document ).ready(function() {
        setFormAuth();

        $("#auth_method").change(function(){
            setFormAuth();

        });
    });

</script>

<div class="form-group">
    <label class="control-label col-md-4">Importer le certificat utilisateur (partie publique au format PEM) :</label>
    <div class="col-md-6">
        <input type="file" name="certificate" />
    </div>
</div>
<?php if ($him->get('certificate')) : ?>
    <div class="alert alert-info col-md-9 col-md-offset-1 " style="word-wrap: break-word;">
        <?php hecho($him->get('subject_dn')) ?>
        <br/>
        Expire le <?php echo $him->getCertificateExpirationDate(); ?>
        <?php if ($me->isSuper()) : ?>
            <br/>
            <a href="<?php echo Helpers::getLink("/admin/users/admin_user_download_cert.php?id=" . $him->getId());?>">Télécharger</a>
        <?php endif ?>
    </div>

    <?php
        $rgsCertificate = new RgsCertificate(OPENSSL_PATH, RGS_VALIDCA_PATH);
        $is_rgs = $rgsCertificate->isRgsCertificate($him->get('certificate'));
        $rgsCertificateExtended = new RgsCertificate(OPENSSL_PATH, $pathToValidCa);
        $has_sslclient_purpose = $rgsCertificateExtended->hasSSlClientPurpose($him->get('certificate'));
    ?>
    <?php if (! $is_rgs) : ?>
        <div class="alert alert-warning col-md-9 col-md-offset-1">
            Ce certificat n'est pas RGS et ne peut pas servir à télétransmettre.
        </div>
    <?php endif; ?>
    <?php if (! $has_sslclient_purpose) : ?>
        <div class="alert alert-warning col-md-9 col-md-offset-1">
            Ce certificat ne semble pas permettre l'authentification à s2low.
        </div>
    <?php endif; ?>

<?php endif;?>
    <div style='clear:both'></div>

<div id="login-form">
<?php $input_label = "Login"; $input_id = "login"?>
<div class="form-group">
    <label class="control-label col-md-4"><?php echo $input_label?> : </label>
    <div class="col-md-6">
        <input class="form-control" type="text" id='<?php echo $input_id ?>' name="<?php echo $input_id ?>" value="<?php echo ($val = Helpers::getFromSession($input_id)) ? get_hecho($val) : get_hecho($him->get($input_id)); ?>" size="30" maxlength="128" />
    </div>
</div>  
    
<?php foreach (array('password' => 'Mot de passe', 'password2' => "Mot de passe (à nouveau)") as $input_id => $input_label) : ?>
<div class="form-group">
    <label class="control-label col-md-4" for="<?php echo $input_id ?>"><?php echo $input_label?>: </label>
    <div class="col-md-6">
        <!-- disables autocomplete https://stackoverflow.com/questions/17781077/autocomplete-off-is-not-working-on-firefox -->
        <input type="text" style="display:none">
        <input type="password" style="display:none">

        <input
                class="form-control"
                id='<?php echo $input_id ?>'
                type="password"
                name="<?php echo $input_id ?>"
                value=""
                size="30"
                maxlength="60"
                autocomplete="off"
        />
    </div>
</div>  
<?php endforeach;?>
</div>

<div class="form-group" id='rgs2-form'>
    <label class="control-label col-md-4">Certificat complémentaire (format PEM) :</label>
    <div class="col-md-6">
        <?php if ($certificat_rgs_2_etoiles_info) : ?>
            <?php hecho($certificat_rgs_2_etoiles_info['name']) ?><br/>
            Expire le : <?php echo $certificat_rgs_2_etoiles_info['expiration_date'] ?> - 
            <a href='admin_user_delete_certificat_rgs_2_etoiles.php?id=<?php echo $him->getId()?>'>Supprimer</a>
            
            <br/><br/>
        <?php endif;?>
        
        <input type="file" name="certificate_rgs_2_etoiles" id="certificate_rgs_2_etoiles" />
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
                <select class="form-control zselect_authorities" name="authority_id">
                    <option value="">Toutes</option>
                    <?php foreach ($authorities_list as $key => $val) : ?>
                        <option
                            value="<?php hecho($key) ?>" <?php echo (strcmp($key, $him_authorities) == 0) ? " selected='selected'" : ""; ?>>
                            <?php hecho($val) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
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



<?php foreach ($modules as $module) : ?>
    <?php if ($me->isSuper() || !empty($authModules[$module["id"]])) : ?>
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

<h2>Autres utilisateurs partageant le même certificat</h2>
<?php if (count($certitificate_id_list) > 1) : ?>
    <p>
        <a href="admin_user_list.php?user_id=<?php hecho($him->getId())?>"><?php echo count($certitificate_id_list)?> utilisateurs</a> partagent ce certificat.
    </p>
<?php endif; ?>

<?php if ($him->get('login') && ($him->get('subject_dn'))) : ?>
    <div class="alert alert-info" style="word-wrap: break-word;">
        <?php hecho($him->get('subject_dn')) ?>
        <br/>
        Expire le <?php echo date("d/m/Y H:i:s", strtotime($x509Certificate->getExpirationDate($him->get('certificate')))); ?>

            <br/>
            <br/>
            <a href='admin_user_edit.php?new_id=<?php echo ($id ? $id : $new_id) ?>' class="btn btn-primary">
                Créer un nouvel utilisateur avec le même certificat
            </a>

    </div>

<?php else : ?>
    Si vous voulez créer un autre utilisateur a partir du même certificat, vous devez saisir le champ login
<?php endif;?>


<?php if ($id && $services_list) : ?>
    <h2>Services</h2>
    <?php if ($userService_list) : ?>
        Cet utilisateur fait partie des services 
        <?php foreach ($userService_list as $i => $s) : ?>
            <?php if ($i != 0) : ?>
                , 
            <?php endif; ?>
            <a href='../services/gestion-service-content.php?id=<?php echo $s['id'] ?>'><?php hecho($s['name']) ?></a>
        <?php endforeach;?>
        
    <?php else :  ?>
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
                    <?php foreach ($services_list as $s) : ?>
                        <option value='<?php echo $s['id'] ?>'><?php hecho($s['name']) ?></option>  
                    <?php endforeach; ?>
                </select>
            </div>
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
