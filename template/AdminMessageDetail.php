<?php
/** @var MessageAdmin $messageAdmin */
/** @var FancyDate $fancyDate */

?>
<h1>Message d'urgence</h1>

<p id="back-transaction-btn">
	<a class="btn btn-default" href="/admin/message/">Revenir à la liste des messages
	</a><br>
</p>

<h2>Message : <?php hecho($messageAdmin->titre) ?></h2>




	<table class="data-table table table-striped">
		<tr>
			<th>Numéro du message</th>
			<td><?php hecho($messageAdmin->message_id) ?></td>
		</tr>
		<tr>
			<th>État</th>
			<td><?php $messageAdmin->displayEtatLabel() ?></td>
		</tr>
		<tr>
			<th>Dernier rédacteur</th>
			<td><?php hecho($messageAdmin->user_name) ?></td>
		</tr>
		<tr>
			<th>Message</th>
			<td>
				<?php $messageAdmin->displayMessage() ?>
			</td>
		</tr>
		<?php if($messageAdmin->getEtat() != MessageAdmin::ETAT_EN_COURS_DE_REDACTION) : ?>
			<tr>
				<th>Date de publication</th>
				<td><?php echo $fancyDate->getDateHeureFrancais($messageAdmin->date_publication)?></td>
			</tr>
			<tr>
				<th>Publieur</th>
				<td><?php hecho($messageAdmin->user_publieur_name)?></td>
			</tr>
			<?php if($messageAdmin->getEtat() == MessageAdmin::ETAT_RETIRE) : ?>
				<tr>
					<th>Date de retrait</th>
					<td><?php echo $fancyDate->getDateHeureFrancais($messageAdmin->date_retrait)?></td>
				</tr>
				<tr>
					<th>Utilisateur ayant retirer le message</th>
					<td><?php hecho($messageAdmin->user_retireur_name)?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>
	</table>

<?php if($messageAdmin->getEtat() != MessageAdmin::ETAT_RETIRE) : ?>
	<h2>Actions</h2>

	<?php if($messageAdmin->getEtat() == MessageAdmin::ETAT_EN_COURS_DE_REDACTION) : ?>
		<a class='btn btn-warning' href="/admin/message/message_publier.php?message_id=<?php hecho($messageAdmin->message_id) ?>">Publier</a></td>

		<a class='btn btn-primary' href="/admin/message/message_edit.php?message_id=<?php hecho($messageAdmin->message_id) ?>">Modifier</a></td>
	<?php endif; ?>
	<?php if($messageAdmin->getEtat() == MessageAdmin::ETAT_PUBLIE) : ?>
		<a class='btn btn-warning' href="/admin/message/message_retirer.php?message_id=<?php hecho($messageAdmin->message_id) ?>">Retirer</a></td>
	<?php endif; ?>
<?php endif; ?>