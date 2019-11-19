<script src="/javascript/mailshow.js" type="text/javascript"></script>
 <h1> Mail - Système de mail sécurisé</h1>
        <h2>Actions</h2>
 	<div id="actions_area"> 
            <a href="index.php?command=create" class="btn btn-primary">Nouveau message</a>
            <a href="index.php?command=list" class="btn btn-primary">Messages envoyés</a>
	</div>
        <h2>Détail du message</h2>

	<div id="list_area">
            <table id="message-detail" class="data-table table table-bordered">
            <?php 
            $mailToSize = 0;
            $mailCcSize = 0;
            $mailBccSize = 0;
            foreach ($mailEmisArray as $mailEmis) 
            { 
                if ($mailEmis->getTypeEnvoi()=="mailTo")
                {
                    $mailToSize++;
                } 
                else if ($mailEmis->getTypeEnvoi()=="mailCC") 
                {
                    $mailCcSize++;
                } 
                else if ($mailEmis->getTypeEnvoi()=="mailBCC") 
                {
                    $mailBccSize++;
                }
            }?>
            <tr><th id="mailto" <?php if ($mailToSize > 1) echo 'rowspan="'.$mailToSize.'"'; ?>>A</th>
            <?php
            $isFirstTo = true;
            foreach ($mailEmisArray as $mailEmis) 
            { 
                if ($mailEmis->getTypeEnvoi()=="mailTo")
                {
                    if ($isFirstTo) 
                    {
                        $isFirstTo=false;
                    } else {
                        echo '<tr>';
                    }
                    echo '<td>'.get_hecho($mailEmis->getEmail()).'';

                    if ($mailEmis->getAck()=='t')
                        echo '<span class="alert alert-info">Réception confirmée le '.$mailEmis->getAckDate().'</span></td></tr>';
                    else
                        echo '<span class="alert alert-info">Pas de confirmation </span></td></tr>';
                }
            }?>
            <tr><th id="mailcc" <?php if ($mailCcSize > 1) echo 'rowspan="'.$mailCcSize.'"'; ?>>CC</th>
            <?php
            $isFirstCc = true;
            foreach ($mailEmisArray as $mailEmis) 
            { 
                if ($mailEmis->getTypeEnvoi()=="mailCC")
                {
                    if ($isFirstCc) 
                    {
                        $isFirstCc=false;
                    } else {
                        echo '<tr>';
                    }
                    
                    echo '<td>'.get_hecho($mailEmis->getEmail()).'';

                    if ($mailEmis->getAck()=='t')
                        echo '<span class="alert alert-info">Réception confirmée le '.$mailEmis->getAckDate().'</span></td></tr>';
                    else
                        echo '<span class="alert alert-info">Pas de confirmation </span></td></tr>'; 
                }
            }?>
            <tr><th id="mailbcc" <?php if ($mailBccSize > 1) echo 'rowspan="'.$mailBccSize.'"'; ?>>CCI</th>
            <?php
            $isFirstBcc = true;
            foreach ($mailEmisArray as $mailEmis) 
            { 
                if ($mailEmis->getTypeEnvoi()=="mailBCC")
                {
                    if ($isFirstBcc) 
                    {
                        $isFirstBcc=false;
                    } else {
                        echo '<tr>';
                    }
                    
                    echo '<td>'.get_hecho($mailEmis->getEmail()).'';
                    
                    if ($mailEmis->getAck()=='t')
                        echo '<span class="alert alert-info">Réception confirmée le '.$mailEmis->getAckDate().'</span></td></tr>';
                    else
                        echo '<span class="alert alert-info">Pas de confirmation </span></td></tr>'; 
                }
            }?>
            <tr>
                <th id="mail-subject">Sujet</th>
                <td><?php hecho($mailTransaction->getObjet()); ?></td>
            </tr>
            <tr>
                <th id="mail-date"><dt>Date d'envoi</th>
                <td><?php echo $mailTransaction->getDateEvnoi(); ?>
            </tr>
            <tr>
                <th id="mail-message">Message</th>
                <td><?php hecho($mailTransaction->getMessage()); ?></td>
            </tr>
    </table>
    <?php 
    if ($mailIncludeFileArray)
    {

        //C'est super dégeulasse...
		/** @var CloudStorage $cloudStorage */
		$cloudStorage  = ObjectInstancierFactory::getObjetInstancier()
			->get(CloudStorageFactory::class)
			->getInstanceByClassName(MailIncludedFilesCloudStorage::class);
		$mailzip_filepath = $cloudStorage->getPath($mailTransaction->getId());


		?>
            <h2>Pièces jointes&nbsp;:</h2>
            <table class="transactions_list table table-bordered table-striped">
                <thead>
                    <tr>
                        <th id="file">Nom du fichier</th>
                        <th id="size">Taille</th>
                        <th id="type">Type</th>
                        <th id="download">Télécharger</th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach ($mailIncludeFileArray as $mailIncludeFile)
                        {?>
                    <tr>
                        <td headers="file"><?php echo $mailIncludeFile->getFileName(); ?></td>
                        <td headers="size"><?php echo $mailIncludeFile->getFileSize(); ?></td>
                        <td headers="type"><?php echo $mailIncludeFile->getFileType(); ?></td>
                        <td headers="download"><a href="template/download.php?filename=<?php echo urlencode($mailIncludeFile->getFileName()); ?>&root=<?php echo $fndownload; ?>">Télécharger</a></td>
                    </tr>
            <?php }?>
                    <tr>
                        <td>&lt;Télécharger tous les fichiers&gt;</td>
                        <td><?php echo filesize($mailzip_filepath); ?></td>
                        <td>zip</td>
                        <td><a href="template/download.php?filename=mail.zip&root=<?php echo $fndownload; ?>">Télécharger</a></td>
                    </tr>
                </tbody> 
            </table> 
	<?php  	}
		else 
		{?>
            <h2>Aucune pièce jointe</h2>
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
