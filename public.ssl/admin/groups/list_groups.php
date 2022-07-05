<?php
require_once(__DIR__ . "/../../../init/init-www.php");

$me = new User();

if (! $me->authenticate()) {
    $_SESSION["error"] = "Ehec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}


$sql = "select count(authorities.id) as count,authority_group_id as id,authority_groups.name FROM authorities " .
    "FULL JOIN authority_groups ON authorities.authority_group_id = authority_groups.id " .
    "GROUP BY authority_group_id,authority_groups.name  ORDER BY authority_groups.name ;";

$groups_list = $sqlQuery->query($sql);


$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle("Configuration de la connexion SAE - S²low");
$doc->openContainer();
$doc->openSideBar();
$doc->addBody($menuHTML->getMenuContent($userInfo, $modulesInfo));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>

    <h1>Groupes de collectivités</h1>
    <p id="back-transaction-btn">
        <a href="<?php echo Helpers::getLink("/admin/groups/admin_groups.php"); ?>" class="btn btn-default">Retour liste groupes</a>
    </p>

    <table class="data-table table table-striped ">
        <tr>
            <th>Groupe</th>
            <th>Nombre de collectivités</th>

        </tr>
        <?php foreach ($groups_list as $i => $group) : ?>
            <tr>
                <?php  if (is_null($group['name'])) :?>
                    <td>
                            <?php hecho("Collectivité(s) sans groupe attaché")  ?>
                    </td>
                    <td>
                        <?php echo $group['count'] ?>
                    </td>
                <?php  else :?>
                    <td><a href="/admin/groups/admin_group_edit.php?id=<?php echo $group['id'] ?>">
                            <?php hecho($group['name'])  ?></a>
                    </td>
                    <td>
                        <?php echo $group['count'] ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach ?>

    </table>
<?php
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();

