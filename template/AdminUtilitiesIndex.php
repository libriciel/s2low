<h1>Utilitaires</h1>


<div id="actions_area">
    <h2>Action</h2>
    <a class="btn btn-primary" href='/admin/utilities/certificate_list.php'>Liste des certificats</a>
    <a class="btn btn-primary" href='/admin/utilities/libersign.php'>Libersign</a>
    <a class="btn btn-danger" href='/admin/utilities/send-critical-message.php'>Déclencher une erreur critique (test)</a>
</div>

<h2 >Envoi de message électronique global</h2>
<div id="global_message">
    <p class="alert-info alert">Utilisez le formulaire ci-dessous pour envoyer un message a l'ensemble des utilisateurs d'un module.</p>
        <form action="/admin/utilities/admin_send_global_message.php" method="post" name="form" onsubmit="return confirm('Voulez-vous vraiment envoyer le message à tous les utilisateurs de ce module');">
            <table class="data-table table table-striped">
                <tr>
                    <th scope="row"><label for="module">Module concerné</label></th>
                    <td>
                        <select name="module" id="module" class="form-control">
                            <option value="">Choisissez</option>
                            <?php foreach ($module_list as $module_id => $module_name) : ?>
                                <option value="<?php echo $module_id; ?>"><?php hecho($module_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="authority_group_id">Groupe concerné</label></th>
                    <td>
                        <select name="authority_group_id" id="authority_group_id" class="form-control">
                            <option value="">Tous les groupes</option>
                            <?php foreach ($group_list as $group_info) : ?>
                                <option value="<?php echo $group_info['id']; ?>"><?php hecho($group_info['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="subject">Sujet du message</label></th>
                    <td>
                        <input id="subject" name="subject" class="form-control" " size="50" maxlength="70"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="body">Message (texte brut)</label></th>
                    <td>
                        <textarea id="body" name="body" class="form-control" rows="20" cols="70" rows="16"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">&nbsp;</th>
                    <td><input type="submit" value="Envoyer le message" class="btn btn-primary"/></td>
                </tr>
            </table>
        </form>
</div>
