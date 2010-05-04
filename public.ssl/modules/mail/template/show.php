<script src="/javascript/mailshow.js" type="text/javascript"></script>
<div id="content">
 <h1> Mail - Système de mail sécurisé</h1>
 	<div id="actions_area"> 
		<a href="index.php?command=create" class="bouton">Nouveau message</a>
		<a href="index.php?command=list" class="bouton">Messages envoyés</a>
	</div>
 <h2>Détail des messages</h2>

	<div id="list_area" style="display:;">
	
		<dt><a href="#tedetis" onclick="toggle_mail_content(1);" id="expander_1" class="expander">-</a>A&nbsp;:</dt>
		<dd id="MailTrans_1" class="mail" style="display: block">
			<table class="transactions_list">
			<?php
			foreach ($mailEmisArray as $mailEmis) 
			{ 
				if ($mailEmis->getTypeEnvoi()=="mailTo")
				{
					echo '<tr><td>'.htmlentities($mailEmis->getEmail()).'</td>';

 				if ($mailEmis->getAck()=='t')
 					echo '<td>Réception confirmée le '.$mailEmis->getAckDate().'</td></tr>';
 				else
 					echo '<td>Pas de confirmation </td></tr>'; 
 				}
 			}?>
 			</table>
 		</dd>
	 	
		<dt><a href="#tedetis" onclick="toggle_mail_content(2);" id="expander_2" class="expander">-</a>CC&nbsp;:</dt>
		<dd id="MailTrans_2" class="mail" style="display: block">
			<table class="transactions_list">
			<?php
			foreach ($mailEmisArray as $mailEmis) 
			{ 
				if ($mailEmis->getTypeEnvoi()=="mailCC")
				{
					echo '<tr><td>'.htmlentities($mailEmis->getEmail()).'</td>';

 				if ($mailEmis->getAck()=='t')
 					echo '<td>Réception confirmée le '.$mailEmis->getAckDate().'</td></tr>';
 				else
 					echo '<td>Pas de confirmation </td></tr>'; 
 				}
 			}?>
 			</table>
 		</dd>
 		
		<dt><a href="#tedetis" onclick="toggle_mail_content(3);" id="expander_3" class="expander">-</a>BCC&nbsp;:</dt>
		<dd id="MailTrans_3" class="mail" style="display: block">
			<table class="transactions_list">	 		
		 			<?php
		 			foreach ($mailEmisArray as $mailEmis) 
		 			{ 
		 				if ($mailEmis->getTypeEnvoi()=="mailBCC")
		 				{
		 			 		echo '<tr><td>'.htmlentities($mailEmis->getEmail()).'</td>';
			 				if ($mailEmis->getAck()=='t')
			 					echo '<td>Réception confirmée le '.$mailEmis->getAckDate().'</td></tr>';
			 				else
			 					echo '<td>Pas de confirmation </td></tr>'; 
			 			}
			 		}?>
		 	</table>
	 	</dd>
		<table class="transactions_list">
			<tr>
				<td class="td_mailAddress">Sujet&nbsp;: </td>
				<td><?php echo $mailTransaction->getObjet(); ?></td>
			</tf>
			<tr>
		 		<td class="td_mailAddress"><dt>Date d'envoi&nbsp;:</dt></td>
		 		<td><?php echo $mailTransaction->getDateEvnoi(); ?>
			</tr>
			<tr>
				<td class="td_mailAddress">Message&nbsp;:</td>
	 			<td><textarea name="message" rows="8" cols="80"><?php echo $mailTransaction->getMessage(); ?></textarea></td>
			</tr>
		</table>
		<?php 
		if ($mailIncludeFileArray)
		{ ?>		
			<h3>Pièces jointes&nbsp;:</h3>
			<table class="transactions_list">
			 	<tr>
			 		<th>Nom du fichier</th>
			 		<th>Taille</th>
			  		<th>Type</th>
			  		<th>Télécharger</th>
			 	</tr>
			<?php foreach ($mailIncludeFileArray as $mailIncludeFile)
			 	 {?>
			 	<tr>
			 		<td><?php echo $mailIncludeFile->getFileName(); ?></td>
			 		<td><?php echo $mailIncludeFile->getFileSize(); ?></td>
			 		<td><?php echo $mailIncludeFile->getFileType(); ?></td>
			 		<td><a href="template/download.php?filename=<?php echo $mailIncludeFile->getFileName(); ?>&root=<?php echo $fndownload; ?>">Télécharger</a></td>
			 	</tr>
			 	<?php }?>
			 	<tr>
					 	<td>&lt;Télécharger tous les fichiers&gt;</td>
					 	<td><?php echo filesize(MAIL_FILES_UPLOAD_ROOT.$fndownload.'/mail.zip'); ?></td>
					 	<td>zip</td>
						<td><a href="template/download.php?filename=mail.zip&root=<?php echo $fndownload; ?>">Télécharger</a></td>
			 	</tr>
			</table> 
	<?php  	}
		else 
		{?>
			<h3>Aucune pièce jointe</h3> 	
	<?php } ?>	
	<?php if ($mailErrors !=false) 
	{
		echo "<h3>L'envoi des messages a echoué</h3>";
		for ($i=0;$i<count($mailErrors);$i++)
		{ ?>
			 <dt><a href="#tedetis" onclick="toggle_mail_error(<?php echo $i; ?>);" id="expander_<?php echo $i; ?>" class="expander">+</a>
			 	Adresse email : <?php echo $mailErrors[$i]['email']; ?> </dt>
			 <dd id="mailError_<?php echo $i;  ?>" class="mailerror" style="display:none"> 
			 <table class="transactions_list">
			 	<td>Message retourné : </td>
			 	<td><?php echo $mailErrors[$i]['message_retour']; ?> </td>
			 </table>
			</dd>
	<?php
		}
	} ?>

</div>
