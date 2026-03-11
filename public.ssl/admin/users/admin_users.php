<?php

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Group;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\JSONoutput;

$jsonOutput = LegacyObjectsManager::getLegacyObjectInstancier()->get(JSONoutput::class);
/** @var HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = LegacyObjectsManager::getLegacyObjectInstancier()->get(HTMLLayoutFactory::class);

$me = new User();

if (! $me->authenticate()) {
    $_SESSION['error'] = "Echec de l'authentification";
    header('Location: ' . Helpers::getLink('connexion-status'));
    exit();
}

if (! $me->isAdmin()) {
    $_SESSION['error'] = 'Accés refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$fauthority = Helpers::getVarFromGet('authority');
$frole =  Helpers::getVarFromGet('role');
$fname = Helpers::getVarFromGet('name');
$fgroup = Helpers::getVarFromGet('group');
$fstatus = Helpers::getVarFromGet('status') ?? 1;
$fcertStatus = Helpers::getVarFromGet('certificat_status');
$api = Helpers::getVarFromGet('api');


$myAuthority = new Authority($me->get('authority_id'));

$filter = [];
// Construction chaîne de filtrage
if ($me->isSuper()) { // Le super utilisateur voit toutes les collectivités et tous les groupes
    if (isset($fauthority) && is_numeric($fauthority)) {
        $filter[] = 'users.authority_id=' . addslashes($fauthority);
    }

    if (isset($fgroup) && is_numeric($fgroup)) {
        $filter[] = 'authorities.authority_group_id=' . addslashes($fgroup);
    }
} elseif ($me->isGroupAdmin()) {
  // Un admin de groupe ne voit forcément que les utilisateurs des collectivité appartenant à son groupe
    if (isset($fauthority) && mb_strlen($fauthority) > 0) {
        $auth = new Authority($fauthority);
        if ($auth->isInGroup($me->get('authority_group_id'))) {
            $filter[] = "users.authority_id='" . addslashes($fauthority) . "'";
        }
    }
    $filter[] = "authorities.authority_group_id='" . $me->get('authority_group_id') . "'";
} elseif ($me->isAuthorityAdmin()) {
    //Un admin d'une collectivité ne voit forcément que les utilisateurs de sa collectivité
    $filter[] = "users.authority_id='" . $me->get('authority_id') . "'";
}

if (isset($frole) && mb_strlen($frole) > 0) {
    $filter[] = "users.role='" . addslashes($frole) . "'";
}

if (isset($fname) && mb_strlen($fname) > 0) {
    $filter[] = "users.name ILIKE '%" . addslashes($fname) . "%'";
}

if (isset($fstatus)) {
    if ($fstatus !== "") {
        $filter[] = "users.status=$fstatus";
    }
} else { // comportement par default, on affiche les utilisateurs activés
    $fstatus = 1;
    $filter[] = "users.status=$fstatus";
}

if ($fcertStatus !== null) {
    if ($fcertStatus === '1') {
        $filter[] = "users.cert_not_after <= NOW()";
    } elseif ($fcertStatus === '0') {
        $filter[] = "users.cert_not_after > NOW()";
    }
}

$where = '';
if (count($filter) > 0) {
    $where = 'WHERE ' . implode(' AND ', $filter);
}

// Récupération de la liste des utilisateurs en fonction du filtre
$users = $me->getUsersList($where);


$statusList = $me->get('statusTypes');
$rolesList = User::ROLES_DESCR;


if ($api) {
    $jsonOutput->retrictAndDisplay(
        $users,
        ['id','name','givenname','email','role','status','authority_id','authority_name']
    );
    exit;
}

if ($me->isAuthorityAdmin()) {
    $title = 'Gestion des utilisateurs de la collectivité «&nbsp;' . get_hecho($myAuthority->get('name')) . '&nbsp;»';
} elseif ($me->isGroupAdmin()) {
    $myGroup = new Group($me->get('authority_group_id'));
    $title = 'Gestion des utilisateurs du groupe «&nbsp;' . get_hecho($myGroup->get('name')) . '&nbsp;»';
} else {
    $title = 'Gestion des utilisateurs';
}

$authority_id_list = [];

if ($me->isGroupAdminOrSuper()) {
    if ($me->isGroupAdmin()) {
        $cond = ' WHERE authorities.authority_group_id=' . $me->get('authority_group_id');
        $cond .= ' ORDER BY authorities.name ASC';
    } else {
        $cond = ' ORDER BY authorities.name ASC';
    }

    $authority_id_list = Authority::getAuthoritiesIdName($cond) ;
}

/*****************/

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle('Tedetis : gestion des utilisateurs');

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->buildPager($me);
$doc->closeSideBar();
$doc->openContent();

ob_start();?>

<script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/jquery.js')?>"></script>
<script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/select2.js')?>"></script>
<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>


<h1><?php echo $title; ?></h1>
<div id="actions-area">
    <h2>Actions</h2>
    <a href="admin_user_edit.php" class="btn btn-primary">Ajouter un utilisateur</a>
</div>
<div id="filtering-area">
    <h2>Filtrage</h2>
        <form action="admin_users.php" method="get" class="form-horizontal">
        <div class="form-group">
            <label for="role" class="col-md-3 control-label">Le rôle est</label>
            <div class="col-md-3"><?php echo $doc->getHTMLSelect('role', User::ROLES_DESCR, $frole) ?></div>
            <label for="name" class="col-md-3 control-label">Le nom contient</label>
            <div class="col-md-3">
                <input
                        id="name"
                        class="form-control"
                        type="text"
                        name="name"
                        size="20"
                        maxlength="25"
                        value='<?php echo  (mb_strlen($fname ?? '') > 0) ? get_hecho($fname) : '' ?>'
                />
            </div>
        </div>
        
        <?php if ($me->isGroupAdminOrSuper()) : ?>
            <div class="form-group">
                <label for="authority" class="col-md-3 control-label">Collectivité</label>
                <div class="col-md-3">
                    <select class="form-control zselect_authorities" name="authority" id="authority">
                        <option value="">Toutes</option>
                        <?php foreach ($authority_id_list as $key => $val) : ?>
                            <option
                                    value="<?php hecho($key) ?>"
                                <?php echo (strcmp($key, $fauthority ?? '') == 0) ? " selected='selected'" : ''; ?>
                            >
                                <?php hecho($val)?> 
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label for="role" class="col-md-3 control-label">L'utilisateur est</label>
                <div class="col-md-3"><?php echo $doc->getHTMLSelect('status', User::STATUS, $fstatus) ?></div>
            </div>
        <?php endif;?>
    
        <?php if ($me->isSuper()) : ?>
            <div class="form-group">
                <label for="group" class="col-md-3 control-label">Groupe</label>
                <div class="col-md-3">
                    <?php echo $doc->getHTMLSelect('group', Group::getGroupsIdName(), $fgroup) ?>
                </div>
                <label for="role" class="col-md-3 control-label">Certificat</label>
                <div class="col-md-3"><?php echo $doc->getHTMLSelect('certificat_status', User::CERT_STATUS, $fcertStatus) ?></div>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <button class="btn btn-default col-md-offset-3 col-md-3" type="submit">Filtrer</button>
        </div>
    </form>
</div>

<h2 id="liste_utilisateurs_desc">Liste des utilisateurs</h2>
<div id="user-list">
    <table class="data-table table table-striped" aria-describedby="liste_utilisateurs_desc">
    <thead>
    <tr>
        <th id="name">Nom</th>
        <th id="email">Adresse électronique</th>
        <th id="role">R&ocirc;le</th>
        <th id="status">Etat</th>
        <th id="authority">Collectivit&eacute;</th>
        <th id="actions">Actions</th>
    </tr>
    </thead>
    <tbody>
    
    <?php foreach ($users as $i => $user) : ?>
    <tr>
        <td headers="name">
            <?php hecho($user['name']) ?> <?php hecho($user['givenname']) ?>
            <?php if ($user['login']) :  ?>
            (<?php hecho($user['login']) ?>)
            <?php endif;?>
            <?php $nb_days_before_expire = floor((strtotime($user['cert_not_after']) - time()) / 86400) ?>
            <?php if ($nb_days_before_expire < 1) : ?>
                <div class="alert alert-danger message-admin">
                    <strong>Certificat expiré</strong>
                </div>
            <?php elseif ($nb_days_before_expire < 30) : ?>
                <div class="alert alert-warning message-admin">
                    <strong>Certificat expire dans <?php echo $nb_days_before_expire ?> jours</strong>
                </div>
            <?php endif; ?>
        </td>
        <td headers="email">
            <a href="mailto:<?php hecho($user['email']) ?>"><?php hecho($user['email']) ?></a>
        </td>
        <td headers="role"><?php echo $rolesList[$user['role']]  ?></td>
        <td headers="status"><?php echo $statusList[$user['status']] ?></td>
        <td headers="authority">
            <a
                    href="<?php
                    echo Helpers::getLink('/admin/authorities/admin_authority_edit.php?id=' . $user['authority_id']);
                    ?>"
            >
                <?php hecho($user['authority_name'])?>
            </a>
        </td>
        <td headers="actions">
            <a
                    href="<?php echo Helpers::getLink('/admin/users/admin_user_edit.php?id=' . $user['id']); ?>"
                    class="icon"
            >
                <img
                        src="<?php echo Helpers::getLink('/custom/images/erreur.png'); ?>"
                        alt="image_modif"
                        title="Modifier"
                />
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
    
    
<?php
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

