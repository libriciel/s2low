<?php

use S2lowLegacy\Class\Helpers;

?>

<h1>Gestion du service <?php hecho($groupe['name']);?></h1>

<p id="back-admin-services-btn"><a class="btn btn-default" href='admin_services.php?authority_id=<?php echo $groupe['authority_id']?>'>Revenir à l'affichage des services</a></p>

<h2>Liste des utilisateurs de <?php hecho($groupe['name'])?></h2>
<div>

    <?php if ($users) : ?>
        <form action='enlever-utilisateur.php' method='post'>
            <input type='hidden' name='id_service' value='<?php echo $id?>'>
            <ul>
                <?php foreach ($users as $u) : ?>
                    <li>
                        <input type='checkbox' name='id_user[]' value='<?php echo $u['id_user']?>'>
                        <a href='../users/admin_user_edit.php?id=<?php echo $u['id_user']?>'><?php echo $u['givenname'] . "&nbsp;" . $u['name']?></a>

                    </li>
                <?php endforeach;?>
            </ul>
            Pour la sélection : <input type='submit' class='btn btn-primary btn-sm' value='enlever du service'/>
        </form>
        <br/>
    <?php else : ?>
        Aucun utilisateur n'est dans le groupe <em><?php hecho($groupe['name'])?></em>.
    <?php endif;?>
    <p>Pour ajouter un utilisateur dans un groupe, allez sur la page de <a href='<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php");?>'> gestion des utilisateurs</a>.
</div>

<h2>Groupe parent</h2>
<div>
    <?php if ($groupe['parent_id']) : ?>
        Le groupe parent de <?php hecho($groupe['name'])?> est <a href='gestion-service-content.php?id=<?php echo $groupe['parent_id']?>'><?php hecho($groupe['parent_name'])?></a>
    <?php else :?>
        <?php hecho($groupe['name'])?> n'a pas de groupe parent.
    <?php endif;?>
</div>
<br/><br/>
<form class="form form-horizontal" action='add-parent.php' method='post'>
    <input type='hidden' name='id' value='<?php echo $id?>'>
    <div class="form-group">
        <label class="label-form col-md-4" for="service_id">Mettre dans le groupe parent :</label>
        <div class="col-md-4">
            <select id="service_id" name='service_id' class='form-control'>
                <option value='0'>(aucun)</option>
                <?php foreach ($all_groupes as $grp) : ?>
                    <option value='<?php echo $grp['id'] ?>' <?php if ($grp['id'] == $groupe['parent_id']) {
                        echo "selected='selected'";
                                   }?>>
                        <?php hecho($grp['name'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <input class='btn btn-primary btn-sm' type='submit' value='Valider'/>
    </div>
</form>


<?php if ($serviceEnfant) : ?>
    <h2>Groupe enfant</h2>
    <?php foreach ($serviceEnfant as $enfant) : ?>
        <a href='gestion-service-content.php?id=<?php echo $enfant['id']?>'><?php hecho($enfant['name'])?></a>&nbsp;
    <?php endforeach;?>
<?php endif;?>


<h2>Suppression</h2>
<p>
    <?php if (! $users && ! $serviceEnfant) : ?>
    <form action="supprimer-service.php" method="post"  onsubmit="return confirm('Voulez-vous vraiment supprimer ce service ?')">
        <input type="hidden" name="id" value="<?php echo $id ?>" />
        <input type="submit" value="Supprimer ce service" class="btn btn-danger" />
    </form>
    <?php else : ?>
    Pour supprimer le service, il faut que celui-ci ne contienne plus d'utilisateur et ne soit pas parent d'un autre service.
    <?php endif;?>
</p>
