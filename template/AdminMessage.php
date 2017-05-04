<?php
/** @var MessageAdmin[] $message_list */
/** @var FancyDate $fancyDate */
?>
<h1>Message d'urgence</h1>

<p id="back-transaction-btn">
	<a class="btn btn-default" href="/admin/index.php">Retour
		</a><br>
</p>

<h2>Actions</h2>
<div id="actions_area">
    <a href="/admin/message/message_edit.php" class="btn btn-primary">Nouveau message</a>
</div>


<h2>Liste des messages</h2>
<div id="authority-list">
    <table class="data-table table table-striped" summary="">
        <thead>
        <tr>
            <th>Titre</th>
            <th>Etat</th>
            <th>Date de publication</th>
            <th>Date de retrait</th>
        </tr>
        </thead>
        <tbody>
		<?php foreach($message_list as $message):?>
            <tr>
                <td><a href="/admin/message/detail.php?message_id=<?php hecho($message->message_id) ?>"><?php hecho($message->titre?:$message->id) ?></a></td>

                <td>
                    <?php $message->displayEtatLabel() ?>
                </td>
                <td>
                    <?php if($message->is_publie) : ?>
                        <?php echo $fancyDate->getDateHeureFrancais($message->date_publication) ?>
                    <?php endif;?>
                </td>
                <td>
					<?php if($message->is_publie) : ?>
						<?php echo $fancyDate->getDateHeureFrancais($message->date_retrait) ?>
					<?php endif;?>
                </td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
</div>