<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\LegacyObjectsManager;

include("init-services.php");

/** @var HTMLLayoutFactory $htmlLayoutFactory */
$htmlLayoutFactory = LegacyObjectsManager::getObject(HTMLLayoutFactory::class);

$authority_id = null;

$authorities = $me->getAllPossibleAuthority();

if (count($authorities) > 1) {
    $authority_id = Helpers::getVarFromGet('authority_id');
} else {
    $authority_id = array_keys($authorities);
    $authority_id = $authority_id[0];
}

$groupes = array();

if ($authority_id) {
    if (!is_numeric($authority_id)) {
        Helpers::returnAndExit(
            1,
            "[admin_services.php] authority_id doit être un entier, $authority_id fourni",
            WEBSITE_SSL
        );
    }
    if (!array_key_exists($authority_id, $authorities)) {
        Helpers::returnAndExit(
            1,
            "[admin_services.php] authorities[$authority_id] n'existe pas",
            WEBSITE_SSL
        );
    }
    $groupes = $serviceUser->getServiceUser($authority_id);
}

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle("Tedetis : gestion des services");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu();
$doc->closeSideBar();
$doc->openContent();

ob_start();?>
    <h1>Gestion des services</h1>

<?php if (count($authorities) > 1) : ?>
    <h2>Choix de la collectivité</h2>
    <?php if (! $authority_id) : ?>
    <ul>
        <?php foreach ($authorities as $id => $name) : ?>
        <li><a href='admin_services.php?authority_id=<?php echo $id?>'><?php hecho($name)?></a></li>
        <?php endforeach;?>
    </ul>
    <?php else : ?>
        <a href='admin_services.php'>Voir une autre collectivité</a>
    <?php endif;?>
<?php endif;?>

<?php if ($authority_id) : ?>
<h2>Liste des services <?php if (count($authorities) > 1) : ?>
(<?php hecho($authorities[$authority_id])?>)
                       <?php endif;?></h2>

<form action='add-service-user.php' method='post' class="form-horizontal">
<input type='hidden' name='authority_id' value='<?php echo $authority_id ?>'/>
<label for='name'> Ajouter un service </label> 
<input type='text' name='name' id='name'/>
<input type='submit' class='btn btn-default' value='ajouter'/>
</form>

<ul>
    <?php foreach ($groupes as $info) : ?>
<li><a href='gestion-service-content.php?id=<?php echo $info['id']?>'><?php hecho($info['name'])?></a> </li>
    <?php endforeach;?>
</ul>
<?php endif;?>
<?php
$html = ob_get_contents();
ob_end_clean();


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();