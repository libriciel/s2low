<?php

class ActesNotification {
	
	private $filePath;

    private $actesTransactionsSQL;
    private $acteTamponne;
    private $authoritySQL;
    private $actesEnveloppeSQL;

    private $mailerFactory;

    private $logger;
	
	public function __construct(
	        ActesTransactionsSQL $actesTransactionsSQL,
            ActeTamponne $acteTamponne,
            AuthoritySQL $authoritySQL,
            ActesEnvelopeSQL $actesEnveloppeSQL,
            MailerFactory $mailerFactory,
            Logger $logger
    ){
		$this->setFilePath(ACTES_FILES_UPLOAD_ROOT);

        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->acteTamponne = $acteTamponne;
        $this->authoritySQL = $authoritySQL;
        $this->actesEnveloppeSQL = $actesEnveloppeSQL;
        $this->mailerFactory = $mailerFactory;
        $this->logger = $logger;
	}
	
	public function setFilePath($filePath){
		$this->filePath = $filePath;
	}
	
	public function sendAutomaticNotification(){
        foreach($this->actesTransactionsSQL->getTransactionToAutoBroadcast() as $transaction_id){
            $this->log("Notification de la transaction $transaction_id");
            $this->sendNotificationManuel($transaction_id);
        }
	}
	
	public function sendNotificationManuel($transactionId){
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
		try {
            $transactionInfo = $this->actesTransactionsSQL->getInfo($transactionId);
            if (! $transactionInfo){
               throw new Exception("La transaction $transactionId n'existe pas");
            }
            $this->sendNotification($transactionInfo,$tmp_folder);
            $tmpFolder->delete($tmp_folder);
        } catch (Exception $e){
            $tmpFolder->delete($tmp_folder);
            throw $e;
        }
		return true;
	}
	
	private function sendNotification(array $transaction_info,$tmp_folder){
        $authority_info = $this->authoritySQL->getInfo($transaction_info['authority_id']);
        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);

		$defaultBroadcastEmail =explode(',',$authority_info['default_broadcast_email']);
		$brodcastEmail =explode(',',$transaction_info['broadcast_emails']);
		$brodcastEmail = array_diff($brodcastEmail,$defaultBroadcastEmail);

		$fichiers_tamponnees =  $this->tamponnerTGZ($this->filePath . "/" . $envelope_info['file_path'],$transaction_info,$tmp_folder);


		if ($transaction_info['auto_broadcasted'] == false){
            //envoie du mail au proprietaire de l'acte
            $this->sendMail($transaction_info,$envelope_info['email'],true,$authority_info['new_notification'],$fichiers_tamponnees);
            //envoie du mail a toutes les adresses renseignees dans defaut
            foreach($defaultBroadcastEmail as $email){
                $this->sendMail($transaction_info,$email,true,false,$fichiers_tamponnees);
            }
            $this->actesTransactionsSQL->setAutoBroadcasted($transaction_info['id']);
		}

		if ($transaction_info['broadcast_emails']){
            foreach($brodcastEmail as $email){
                $this->sendMail($transaction_info,$email,$transaction_info['broadcast_send_sources'] == 1,false,$fichiers_tamponnees);
            }
            $this->actesTransactionsSQL->setBroadcasted($transaction_info['id']);
		}
	}

	private function sendMail($transactionInfo,$emails,$withFile,$add_url_recup,array $fichiers_tamponnees){
		if (! $emails){
			return;
		}

		$mailer = $this->mailerFactory->getInstance();

		$err = $mailer->addRecipient($emails);
        if (! $err){
            $this->log("$emails invalide !");
        }

        if($withFile && ! $add_url_recup){
            foreach($fichiers_tamponnees as $fichier){
                $mailer->addFile($fichier);
            }
        }

        $status_info = $this->actesTransactionsSQL->getStatusInfo($transactionInfo['id'],4);

        $ar_actes_filename = "{$transactionInfo['unique_id']}-{$transactionInfo['type']}-{$transactionInfo['id']}-AR.xml";
        $mailer->addStringAsFile($ar_actes_filename, $status_info['flux_retour']);

        $mailContent = $this->getMailContent($transactionInfo,$add_url_recup);

		$pdf=new ActesPdf();
		$pdf->addEmailNotificationField();
		$pdf->create_pdf($transactionInfo['id']);
		$monpdf = $pdf->output("bordereau_acquittement","S");
		$mailer->addStringAsFile("bordereau_acquittement.pdf",$monpdf);

        $authority_info = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

        $mailer->sendMail(
                "[{$authority_info['name']}] Notification d'accusé de réception pour l'acte " . $transactionInfo['number'] ,
                $mailContent
        );
	}

	private function getMailContent($transaction_info,$add_url_recup){
        $status_info = $this->actesTransactionsSQL->getStatusInfo($transaction_info['id'],4);
        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);


        ob_start();?>
<?php if ($transaction_info['type'] == 1) : ?>
L'acte de référence interne <?php echo $transaction_info['number'] ?> a été acquitté sous l'identifiant unique <?php echo $transaction_info['unique_id']  ?>.
<?php elseif ($transaction_info['type'] == 3) : ?>
L'envoi de pièces complémentaires (ou du refus explicite) concernant l'actes <?php echo $transaction_info['number'] ?> a été acquitté.
<?php elseif ($transaction_info['type'] == 4) : ?>
L'envoi de la lettre d'observation (ou du refus de réponse) concernant l'actes <?php echo $transaction_info['number'] ?> a été acquitté.
<?php endif; ?>

Nature de l'Acte : <?php echo $transaction_info['nature_descr'] ?>

Objet : <?php echo $transaction_info['subject'] ?>

Décision du : <?php echo $transaction_info['decision_date']?>

Transmise le :  <?php echo $envelope_info['submission_date']?>

Accusé reçu le :  <?php echo $status_info['date'] ?>

<?php if($add_url_recup) : ?>
URL pour récupérer les fichiers : <?php $url = WEBSITE_SSL."/modules/actes/actes_transac_show.php?id=".$transaction_info['id']; echo $url; ?>
<?php endif; ?>

<?php if($transaction_info['archive_url']) : ?>
Archive disponible sur :<?php echo $transaction_info['archive_url']?>
<?php endif; ?> 
		<?php 
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	
	private function tamponnerTGZ($filePath,$transactionInfo,$tmp_folder){
        $pharData = new PharData($filePath);
        $pharData->extractTo($tmp_folder);

		$result = array();
		$files = array_diff(scandir($tmp_folder),array('.','..'));
		foreach($files as $file){
		    $file = $tmp_folder."/$file";
			if (pathinfo($file,PATHINFO_EXTENSION) == 'pdf'){
				$fileString = $this->acteTamponne->tamponnerPDF($file,$transactionInfo['id']);
				file_put_contents($file,$fileString);
			}
			$result[] = $file;
		}
		return $result;
	}

    private function log($message){
        $this->logger->log("actes-notification",$message);
    }
}
