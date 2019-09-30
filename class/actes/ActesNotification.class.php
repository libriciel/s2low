<?php

class ActesNotification {

    private $actesTransactionsSQL;
    private $acteTamponne;
    private $authoritySQL;
    private $actesEnveloppeSQL;

    private $mailerFactory;

    private $logger;

    private $actes_appli_trigramme;

    private $actesRetriever;

	public function __construct(
	        ActesTransactionsSQL $actesTransactionsSQL,
            ActeTamponne $acteTamponne,
            AuthoritySQL $authoritySQL,
            ActesEnvelopeSQL $actesEnveloppeSQL,
            MailerFactory $mailerFactory,
            S2lowLogger $logger,
            $actes_appli_trigramme,
            ActesRetriever $actesRetriever
    ){
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->acteTamponne = $acteTamponne;
        $this->authoritySQL = $authoritySQL;
        $this->actesEnveloppeSQL = $actesEnveloppeSQL;
        $this->mailerFactory = $mailerFactory;
        $this->logger = $logger;
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->actesRetriever = $actesRetriever;
	}

	/**
	 * @throws Exception
	 */
	public function sendAutomaticNotification(){
		$sigtermHandler = SigTermHandler::getInstance();
        foreach($this->actesTransactionsSQL->getTransactionToAutoBroadcast() as $transaction_id){
            $this->logger->info("Notification de la transaction $transaction_id");
            $this->sendNotificationManuel($transaction_id);
            if ($sigtermHandler->isSigtermCalled()){
                break;
            }
        }
	}

	/**
	 * @param $transactionId
	 * @return bool
	 * @throws Exception
	 */
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

	/**
	 * @param array $transaction_info
	 * @param $tmp_folder
	 * @throws Exception
	 */
	private function sendNotification(array $transaction_info,$tmp_folder){
        $authority_info = $this->authoritySQL->getInfo($transaction_info['authority_id']);
        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);

		$defaultBroadcastEmail =explode(',',$authority_info['default_broadcast_email']);
		$brodcastEmail =explode(',',$transaction_info['broadcast_emails']);
		$brodcastEmail = array_diff($brodcastEmail,$defaultBroadcastEmail);

		$archive_path = $this->actesRetriever->getPath($envelope_info['file_path']);

		$fichiers_tamponnees =  $this->tamponnerTGZ($archive_path,$transaction_info,$tmp_folder);


		if (! $transaction_info['auto_broadcasted']){
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
			$this->logger->info("$emails invalide !");
        }

        if($withFile && ! $add_url_recup){
            foreach($fichiers_tamponnees as $fichier){
                $mailer->addFile($fichier);
            }
        }

        $status_info = $this->actesTransactionsSQL->getStatusInfo($transactionInfo['id'],4);
        if ($status_info) {
            $ar_actes_filename = "{$transactionInfo['unique_id']}-{$transactionInfo['type']}-{$transactionInfo['id']}-reponse.xml";
            $mailer->addStringAsFile($ar_actes_filename, $status_info['flux_retour']);
            $pdf = new ActesPdf();
            $pdf->addEmailNotificationField();
            $pdf->create_pdf($transactionInfo['id']);
            $monpdf = $pdf->output("bordereau_acquittement", "S");
            $mailer->addStringAsFile("bordereau_acquittement.pdf", $monpdf);
        }

        $mailContent = $this->getMailContent($transactionInfo,$add_url_recup);

        $authority_info = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

        $mailer->sendMail(
                "[{$authority_info['name']}] Notification concernant l'acte " . $transactionInfo['number'] ,
                $mailContent
        );

        $message_log =
            sprintf(
                "[%s] Transaction %s (%d) : Envoi d'une notification à %s. Numéro SIREN de la collectivité : %s. Type de transaction: %d",
                $this->actes_appli_trigramme,
                $transactionInfo['unique_id'],
                $transactionInfo['id'],
                $emails,
                $authority_info['siren'],
                $transactionInfo['type']
            );
        Log::newEntry(LOG_ISSUER_NAME,$message_log,1,false,"USER","actes",false,$transactionInfo['user_id']);
    }

	private function getMailContent($transaction_info,$add_url_recup){

	    $last_status_id = $transaction_info['last_status_id'];

	    if ($last_status_id == ActesStatusSQL::STATUS_ACQUITTEMENT_RECU) {
            $status_info = $this->actesTransactionsSQL->getStatusInfo($transaction_info['id'], $last_status_id);
        } else {
            $status_info = $this->actesTransactionsSQL->getStatusInfo($transaction_info['id'], ActesStatusSQL::STATUS_DOCUMENT_RECU);
        }


        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);

        ob_start();?>

<?php if ($last_status_id == -1) : ?>
            L'acte de référence interne <?php echo $transaction_info['number'] ?> est en erreur.
<?php elseif ($transaction_info['type'] == 1) : ?>
L'acte de référence interne <?php echo $transaction_info['number'] ?> a été acquitté sous l'identifiant unique <?php echo $transaction_info['unique_id']  ?>.
<?php elseif ($transaction_info['type'] == 3 && $last_status_id == 4) : ?>
L'envoi de pièces complémentaires (ou du refus explicite) concernant l'actes <?php echo $transaction_info['number'] ?> a été acquitté.
<?php elseif ($transaction_info['type'] == 4 && $last_status_id == 4) : ?>
L'envoi de la lettre d'observation (ou du refus de réponse) concernant l'actes <?php echo $transaction_info['number'] ?> a été acquitté.
<?php elseif ($transaction_info['type'] == 6 && $last_status_id == 4) : ?>
L'annulation de l'acte <?php echo $transaction_info['number'] ?> a été acquittée.
<?php else:?>
Réception de document pour l'acte  <?php echo $transaction_info['number'] ?>
<?php endif; ?>


Nature de l'Acte : <?php echo $transaction_info['nature_descr'] ?>

Objet : <?php echo $transaction_info['subject'] ?>

Décision du : <?php echo $transaction_info['decision_date']?>

Transmise le :  <?php echo $envelope_info['submission_date']?>

<?php if ($last_status_id == 4): ?>
Accusé reçu le :  <?php echo $status_info['date'] ?>
<?php else: ?>
Document reçu le :  <?php echo $status_info['date'] ?>
<?php endif; ?>

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


	/**
	 * @param $filePath
	 * @param $transactionInfo
	 * @param $tmp_folder
	 * @return array
	 * @throws Exception
	 */
	private function tamponnerTGZ($filePath,$transactionInfo,$tmp_folder){

		$command = "tar xzf $filePath --directory $tmp_folder 2>&1";
		$this->logger->debug("Executing comand : $command");
		exec($command, $output, $return_var);
		if ($return_var != 0) {
			throw new Exception("Erreur ($return_var) lors de la décompression de l'archive $filePath : " . implode("\n", $output));
		}

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

}
