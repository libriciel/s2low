<?php

/** @var MessageAdmin $messageAdmin */

use S2lowLegacy\Model\MessageAdmin;

?>
<h1>Message d'urgence</h1>

<p id="back-transaction-btn">
    <a class="btn btn-default" href="/admin/message/">Revenir à la liste des messages
    </a><br>
</p>

<h2 id = "edition_desc">Édition d'un message</h2>


<form action="/admin/message/do_message_edit.php" method="POST">
    <input type="hidden" name="message_id" value="<?php hecho($messageAdmin->message_id)?>"/>
    <table class="data-table table table-striped" aria-describedby="edition_desc">
        <tr>
            <th scope="row"><label for="titre">Titre</label></th>
            <td>
                <input id="titre" name="titre" class="form-control" value="<?php hecho($messageAdmin->titre) ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="niveau">Niveau</label></th>
            <td>
                <select name="niveau" id="niveau">
                    <?php foreach ($messageAdmin->getLibelleNiveau() as $niveau => $libelle) :?>
                        <option value="<?php hecho($niveau)?>" <?php echo $niveau == $messageAdmin->niveau ? 'selected' : '' ?>>
                            <?php hecho($libelle) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </td>
        </tr>
        <tr>
            <th scope="row"><label for="message">Message</label></th>
            <td>
                <textarea id="message" name="message" class="form-control" rows="20"><?php hecho($messageAdmin->message) ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">&nbsp;</th>
            <td><input type="submit" value="Enregistrer" class="btn btn-primary"/></td>
        </tr>

    </table>

</form>
<br/><br/>
<div class="alert alert-info">
    Il est possible de mettre du <a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet" target="_blank">Markdown</a> dans le message.

</div>