<div id="content">
 <h1> Mail - Système de mail sécurisé</h1>

  	<h2>Actions</h2>
  	<div id="actions_area"> 
			<a href="index.php?command=create" class="bouton">Nouveau message</a>
	</div>
<?php if ($deleteMessage != null) 
	{	foreach($deleteMessage as $message)
			echo "<p>$message</p>";
	}
?>
	<h2 class="toggle_title" onclick="javascript:toggle_visibility('filtering_area');">Filtrage</h2>
	<div id="filtering_area" style="display: block;">	
		<form action="index.php?command=list" method="post" accept-charset="utf-8">
			<input type="hidden" name="search" value="1" />
			<table>
				<tr>
					<td class="title">Type d'état&nbsp;:</td>
					<td class="value">
						<select name="etat">
						 	<option value="0" <?php echo $etat==0?"selected='selected'":"" ?>>Tous</option>
							<option value="1" <?php echo $etat==1?"selected='selected'":"" ?>>Confirmation par tous les destinataires</option>
						 	<option value="2" <?php echo $etat==2?"selected='selected'":"" ?> >Confirmation par aucun des destinataires</option>
						 	<option value="3"  <?php echo $etat==3?"selected='selected'":"" ?> >Confirmation par certains destinataires</option>
						</select>
					</td>	
					<td></td>	
					<td class="title">Sujet&nbsp;:</td>
					<td class="value"><input type="text" name="sujet" size="20" maxlength="25" value='<?php echo $sujet?>' /></td>

				</tr>
				<tr><td>Date d'envoi</td></tr>
				<tr>
					<td class="title">À partir du </td>
					<td>
					<input id="send_date_from" name="SendDateFrom" type="hidden" value="<?php $SendDateFrom; ?>"/>
						<script type="text/javascript">
						//<![CDATA[
						 obj_send_date_from=new DatePicker('send_date_from', 'fr');
						 //]]>
						</script>
						<a href="#datepicker" id="datepicker_send_date_from_link" class="datepicker_link" onclick="javascript:obj_send_date_from.toggleDatePicker(); return false;">
							<?php 
							if ($SendDateFrom) {
							  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
							  echo strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($SendDateFrom));
							} else {
							  echo "[&nbsp;Choisir une date&nbsp;]";
							}
							?>
						</a>
						<div class="date_picker" style="display: none;" id="datepicker_send_date_from_calendar"></div>
					</td>
					<td class="title">jusqu'au</td>
					<td>
						<input id="send_date_to" name="SendDateTo" type="hidden" value="<?php $SendDateTo; ?>"/>
						<script type="text/javascript">
						//<![CDATA[
						 obj_send_date_to=new DatePicker('send_date_to', 'fr');
						 //]]>
						</script>
						<a href="#datepicker" id="datepicker_send_date_to_link" class="datepicker_link" onclick="javascript:obj_send_date_to.toggleDatePicker(); return false;">
							<?php 
							if ($SendDateTo) {
							  setlocale(LC_TIME, "fr_FR.ISO-8859-15@euro");
							  echo strftime("%e %B %Y", Helpers :: ansiDateToTimestamp($SendDateTo));
							} else {
							  echo "[&nbsp;Choisir une date&nbsp;]";	
							}
							?>
						</a>
						<div class="date_picker" style="display: none;" id="datepicker_send_date_to_calendar"></div>
					</td>
				</tr>
			
				<tr>
					<td colspan="4"><input class="submit_button" type="submit" value="Filtrer" /></td>
					<td colspan="4"><a href="index.php?command=list" class="bouton">Remettre&nbsp;à&nbsp;zéro</a></td>
				</tr>
			</table>
		</form>
	</div>
		

  	<h2 class="toggle_title" onclick="javascript:toggle_visibility('list_area');" >Messages Envoyés</h2>
	<div id="list_area" style="display:;">
		<div>
			<a href="#tedetis" onclick="javascript:show_all();" title="Déplier toutes les emails">[&nbsp;Tout déplier&nbsp;]</a>
			<a href="#tedetis" onclick="javascript:hide_all();" title="Replier toutes les emails">[&nbsp;Tout replier&nbsp;]</a>
		</div>
		<p></p>
		<form action="index.php?command=list" method="post">
			
		
		
		<?php 
		$i=0; // le numéro des éléments dans la liste commence par 1 donc dans la fichier de javascript le i commence aussi par 1 
		foreach ($MailTransactions as $MailTrans)
		{
			$i++; ?>
		
			<dt><a href="#tedetis" onclick="toggle_mail_content(<?php echo $i; ?>);" id="expander_<?php echo $i; ?>" class="expander">-</a>
			mail::<?php echo $MailTrans["objet"]; ?>
			</dt>
		
			<dd id="MailTrans_<?php echo $i; ?>" class="mail" style="display: block">
				<table class="transactions_list">
					<tr>
						<th>Sélection</th> 
						<th>Objet</th>
						<th>Statuts</th>
						<th>Date d'envoi</th>
						<th>Détail</th>
					</tr>
					<tr>
						<td><input type="checkbox" name="list_id[]" value="<?php echo $MailTrans["id"]; ?>" /></td>
						<td> <?php echo $MailTrans["objet"]?></td>
						<td> <?php echo $MailTrans["status"] ?></td>	
				 		<td> <?php echo $MailTrans["date_envoi"]?></td>
				 		<td><a href="index.php?command=show&trans_id=<?php echo $MailTrans["id"]; ?>"><img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" alt="image_modif" title="Afficher le détail"></a></td>
					</tr>
				</table>
			</dd>
		<?php 
		} ?>
		<p><input type="submit" class="submit_button" value="Supprimer les messages sélectionnés" /></p>
		</form>
	</div>


