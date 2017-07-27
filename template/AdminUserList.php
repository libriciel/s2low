<h1><?php hecho($title) ?></h1>

<p id="back-transaction-btn">
    <a class="btn btn-default" href="/admin/users/admin_user_edit.php?id=<?php hecho($user_id) ?>">Retour
    </a><br>
</p>

<h2>Certificat partagé</h2>
<div class="alert alert-info" style="word-wrap: break-word;">
    <?php hecho($user_info['subject_dn']) ?>
    <br/>
    Expire le <?php hecho($certificat_expiration) ?>
        <br/>
        <br/>
        <a href='admin_user_edit.php?new_id=<?php echo $user_id ?>' class="btn btn-primary">
            Créer un nouvel utilisateur avec le même certificat
        </a>
</div>

<h2>Liste des utilisateurs</h2>



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
        <?php foreach($user_list as $i => $info): ?>
            <tr >
                <td><?php hecho($info['login']) ?></td>
                <td><?php hecho($info['givenname'] . " ".$info['name']) ?></td>
                <td><a href="mailto: <?php hecho($info['email']) ?>"><?php hecho($info['email']) ?></a></td>
                <td><?php echo $roles_type_list[$info['role']] ?></td>
                <td><?php echo $status_type_list[$info["status"]] ?></td>
                <td><a href="<?php echo WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $info["authority_id"] ?>"><?php hecho($info["authority_name"]) ?></a></td>
                <td>
                    <a href="<?php echo WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" .  $info['id'] ?>" class="icon">
                        <img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" alt="image_modif" title="Modifier" />
                    </a>
                </td>
            </tr>
        <?php endforeach;?>
    </table>
</div>
