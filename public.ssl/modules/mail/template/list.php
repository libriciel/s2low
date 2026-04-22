 <h1> Mail - Système de mail sécurisé</h1>

 <?php

 /**
  * @var string $etat
  * @var string $sujet
  * @var string|null $SendDateFrom
  * @var string|null $SendDateTo
  * @var MailTransaction $MailTransactions
  */

 use S2lowLegacy\Class\DatePicker;
 use S2lowLegacy\Class\Helpers;
 use S2lowLegacy\Mail\MailTransaction;

 if (isset($_SESSION['last_message'])) : ?>
     <div class="alert alert-success" >
         <?php echo $_SESSION['last_message']; ?>
     </div>
     <?php
        unset($_SESSION['last_message']);
 endif;?>

 <?php if (isset($_SESSION['last_error'])) : ?>
     <div class="alert alert-danger" >
         <?php echo $_SESSION['last_error']; ?>
     </div>
        <?php
        unset($_SESSION['last_error']);
 endif;?>

    <h2>Actions</h2>
    <div id="actions_area"> 
            <a href="index.php?command=create" class="btn btn-primary">Nouveau message</a>
    </div>
<?php if (! empty($deleteMessage)) {
    foreach ($deleteMessage as $message) {
            echo "<p>$message</p>";
    }
}
?>
    <h2 class="toggle_title" onclick="javascript:toggle_visibility('filtering_area');">Filtrage</h2>
    <div id="filtering_area">
            <form action="index.php?command=list" accept-charset="utf-8" role="form" class="form-horizontal">
                <input type="hidden" name="search" value="1" />
                <div class="form-group">
                    <label for="state-type" class="col-md-2 control-label">Type d'état</label>
                    <div class="col-md-4">
                        <select id="state-type" class="form-control" name="etat">
                            <option value="0" <?php echo $etat == 0 ? "selected='selected'" : '' ?>>
                                Tous
                            </option>
                            <option value="1" <?php echo $etat == 1 ? "selected='selected'" : '' ?>>
                                Confirmation par tous les destinataires
                            </option>
                            <option value="2" <?php echo $etat == 2 ? "selected='selected'" : '' ?> >
                                Confirmation par aucun des destinataires
                            </option>
                            <option value="3"  <?php echo $etat == 3 ? "selected='selected'" : '' ?> >
                                Confirmation par certains destinataires
                            </option>
                        </select>
                    </div>
                    <label for="subject" class="col-md-2 control-label">Sujet</label>
                    <div class="col-md-4">
                        <input id="subject" class="form-control" type="text" name="sujet" size="20" value='<?php hecho($sujet) ?>' />
                    </div>
                </div>
                <div class="form-group">
                    <label for="send_date_from" class="col-md-2 control-label">Date d'envoi à partir du </label>
                    <div class="col-md-4 sub-date">
                        <?php echo (new DatePicker('SendDateFrom', $SendDateFrom))->show();?>
                    </div>
                    <label for="send_date_to" class="col-md-2 control-label">Date d'envoi jusqu'au</label>
                    <div class="col-md-4 sub-date">
                        <?php echo (new DatePicker('SendDateTo', $SendDateTo))->show();?>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="col-md-offset-2 col-md-2 btn btn-default">Filtrer</button>
                    <a href="index.php?command=list" class="col-md-offset-4 col-md-2 btn btn-default">Remise à zéro</a>
                </div>
            </form>
    </div>
        

    <h2 class="toggle_title" onclick="javascript:toggle_visibility('list_area');" >Messages Envoyés</h2>
        <?php
        if ($MailTransactions) { ?>
    <div id="sent-message-area">
            <div id="display-actions">
                <a
                        href="#tedetis"
                        onclick="javascript:show_all();"
                        title="Déplier toutes les emails" class="btn btn-default">
                    Tout déplier
                </a>
                <a
                        href="#tedetis"
                        onclick="javascript:hide_all();"
                        title="Replier toutes les emails"
                        class="btn btn-default">
                    Tout replier
                </a>
            </div>

            <form
                    action="index.php?command=list"
                    method="post"
                    onsubmit="return confirm('Êtes-vous certain de vouloir supprimer ces emails ?');"
            >
                <dl>
            <?php
            $i = 0; // le numéro des éléments dans la liste commence par 1 donc dans la fichier de javascript
            //  le i commence aussi par 1

            foreach ($MailTransactions as $MailTrans) {
                    $i++; ?>
        
                <dt>
                    <a
                            href="#tedetis" onclick="toggle_mail_content(<?php echo $i; ?>);"
                            id="expander_<?php echo $i; ?>"
                            class="expander btn btn-default btn-xs"
                    >
                        -
                    </a>
                mail::<?php hecho($MailTrans['objet']); ?>
                </dt>

                <dd id="MailTrans_<?php echo $i; ?>" class="mail" style="display: block">
                    <table class="transactions_list data-table table table-bordered">
                        <caption>Liste des message envoyés</caption>
                        <thead>
                            <tr class="active">
                                <th id="selection">Sélection</th> 
                                <th id="object">Objet</th>
                                <th id="status">Statut</th>
                                <th id="date">Date d'envoi</th>
                                <th id="detail">Détail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td headers="selection">
                                    <input type="checkbox" name="list_id[]" value="<?php echo $MailTrans['id']; ?>" />
                                </td>
                                <td headers="object"> <?php hecho($MailTrans['objet'])?></td>
                                <td headers="status"> <?php echo $MailTrans['status'] ?></td>
                                <td headers="date"> <?php echo $MailTrans['date_envoi']?></td>
                                <td headers="detail">
                                    <a href="index.php?command=show&trans_id=<?php echo $MailTrans['id']; ?>">
                                        <img
                                                src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/custom/images/erreur.png'); ?>"
                                                alt="image_modif" title="Afficher le détail"
                                        >
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </dd>
                <?php
            } ?>
                </dl>
                    <div id="actions">
                        <input type="submit" class="btn btn-default" value="Supprimer les messages sélectionnés" />
                    </div>
            </form>
    </div>
            <?php
        } else {
            ?>  
        <p>Pas de messages envoyés correspondant aux critères de filtrage </p>
        <?php } ?>
