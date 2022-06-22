<?php
require_once(__DIR__ . "/../../../init/init-www-actes.php");

$actesStatistiques = new ActesStatistiques($sqlQuery);


$title = "Statistiques de transmission des enveloppes ";
if ($droit->isSuperAdmin($userInfo)) {
    $title .= " pour l'ensemble des collectivités";
} elseif ($droit->isGroupAdmin($userInfo)) {
    $title .= "pour le groupe " . $groupeInfo["name"];
    $actesStatistiques->setGroup($userInfo['authority_group_id']);
} elseif ($droit->isAuthorityAdmin($userInfo)) {
    $title .= " pour la collectivité " . $authorityInfo["name"];
    $actesStatistiques->setAuthority($userInfo['authority_id']);
} else {
    $title .= " pour l'utilisateur " . $userInfo['pretty_name'];
    $actesStatistiques->setUser($connexion->getId());
}

$statInfo = $actesStatistiques->getInfo();


$currentMonth = date('Y-m-01 00:00:00');
$currentYear = date('Y-01-01 00:00:00');

$filter = array();

$menuHTML = new MenuHTML();


$doc = new HTMLLayout();
$doc->setTitle("Statistiques - ACTES - S²low");
$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($userInfo, $modulesInfo));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>
        <h1>ACTES - Dématérialisation du contrôle de légalité</h1>
        <h2><?php echo $title ?></h2>
            <dl>
                <?php
                foreach (
                    array(date("Y-m-01") => "Depuis le début du mois",
                    date("Y-01-01") =>
                    "Depuis le début de l'année", "1970-01-01" => "En totalité") as $date => $titre
                ) :
                    ?>
                    <dt><?php echo $titre ?>&nbsp;:</dt>
                    <dd>
                        <ul>
                            <li>Nombre d'enveloppes postées : <?php echo $statInfo[$date]['nb_envelope'] ?></li>
                            <li>Nombre d'enveloppes transmises au ministère : <?php echo $statInfo[$date]['nb_envelope_poste'] ?></li>
                            <li>Volume des enveloppes postées sur le tdt&nbsp;: <?php echo $statInfo[$date]['volume'] ?> octets</li>
                            <li>Volume des enveloppes transmises au ministère&nbsp;: <?php echo $statInfo[$date]['volume_poste'] ?> octets</li>
                        </ul>
                <?php endforeach; ?>
                </dd>
            </dl>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    $doc->addBody($html);

    $doc->closeContent();
    $doc->closeContainer();

    $doc->buildFooter();
    $doc->display();


