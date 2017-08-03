<?php

class ActesNotification {
	
	private $lesFichiers;
	private $filePath;
    private $pdfgenerate = false;
    private $pdftampone;
    private $newmail=true;
    private $agent=false;

    private $actesTransactionsSQL;
    private $acteTamponne;
    private $authoritySQL;
    private $actesEnveloppeSQL;

    private $sqlQuery;
	
	public function __construct(
	        ActesTransactionsSQL $actesTransactionsSQL,
            ActeTamponne $acteTamponne,
            AuthoritySQL $authoritySQL,
            ActesEnvelopeSQL $actesEnveloppeSQL,
            SQLQuery $sqlQuery
    ){
		$this->lesFichiers = array();
		$this->setFilePath(ACTES_FILES_UPLOAD_ROOT);

        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->acteTamponne = $acteTamponne;
        $this->authoritySQL = $authoritySQL;
        $this->actesEnveloppeSQL = $actesEnveloppeSQL;

        $this->sqlQuery = $sqlQuery;
	}
	
	private function setFilePath($filePath){
		$this->filePath = $filePath;
	}
	
	public function sendAutomaticNotification(){
        foreach($this->actesTransactionsSQL->getTransactionToAutoBroadcast() as $transaction_id){
            echo "Notification de la transaction $transaction_id\n";
            $this->sendNotificationManuel($transaction_id);
        }
	}
	
	public function sendNotificationManuel($transactionId){
		$transactionInfo = $this->getTransactionInfo($transactionId);
		if (! $transactionInfo){
			return false;
		}
		$this->sendNotification($transactionInfo);
		$this->deletedirectoryunzip($transactionInfo);
		return true;
	}
	
	private function sendNotification(array $transaction_info){
        $authority_info = $this->authoritySQL->getInfo($transaction_info['authority_id']);

        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);

		$defaultBroadcastEmail =explode(',',$authority_info['default_broadcast_email']);
		$brodcastEmail =explode(',',$transaction_info['broadcast_emails']);
		$brodcastEmail = array_diff($brodcastEmail,$defaultBroadcastEmail);

        $this->newmail = $authority_info['new_notification'];
                
		if ($transaction_info['auto_broadcasted'] == false){
            //envoie du mail au proprietaire de l'acte
            $this->agent=true;
            $this->sendMail($transaction_info,$envelope_info['email'],true);
            $this->agent=false;
            //envoie du mail a toutes les adresses renseignees dans defaut
            foreach($defaultBroadcastEmail as $email){
                $this->sendMail($transaction_info,$email,true);
            }
            $this->actesTransactionsSQL->setAutoBroadcasted($transaction_info['id']);
		}
                
		if ($transaction_info['broadcast_emails']){
            foreach($brodcastEmail as $email){
                $this->sendMail($transaction_info,$email,$transaction_info['broadcast_send_sources'] == 1);
            }
            $this->actesTransactionsSQL->setBroadcasted($transaction_info['id']);
		}
                
        $this->pdfgenerate = false;
	}
	
	private function sendMail($transactionInfo,$emails,$withFile){
		if (! $emails){
			return;
		}

		$mailer = new Mailer();
        if(($this->agent && $this->newmail == 'f') || !$this->agent){
            if(! $this->pdfgenerate){
                $lesFichiers = $this->getFichiers($transactionInfo);
            } else {
                $lesFichiers = $this->pdftampone;
            }

            if ($withFile){
              foreach ($lesFichiers as $fichier){
                $mailer->addFile($fichier);
              }
            }
        }


        $err = $mailer->addRecipient($emails);
        if (! $err){
                echo "$emails invalide ! \n";
                return;
        }

        $trans = new ActesTransaction();
        $trans->setId($transactionInfo['id']);
        $trans->init();
        if($this->newmail == 't') {
            $mailer->addStringAsFile("retour.xml", $trans->getFluxRetour(4));
        }

        $mailContent = $this->getMailContent($transactionInfo);

		$pdf=new ActesPdf();
		$pdf->addEmailNotificationField();
		$pdf->create_pdf($transactionInfo['id']);
		$monpdf = $pdf->output("bordereau_acquittement","S");
		$mailer->addStringAsFile("bordereau_acquittement.pdf",$monpdf);
		
		$mailer->sendMail("[{$transactionInfo['name']}] Notification d'accusé de réception pour l'acte " . $transactionInfo['number'] , $mailContent);
	}
	
	private function getTransactionInfo($transactionId){
        return $this->actesTransactionsSQL->getInfo($transactionId);
	}
	
	private function getMailContent($transactionInfo){
		ob_start();?>
L'acte de référence interne <?php echo $transactionInfo['number'] ?> a été acquitté sous l'identifiant unique <?php echo $transactionInfo['unique_id']  ?>


Nature de l'Acte : <?php echo $transactionInfo['nature_descr'] ?>

Objet : <?php echo $transactionInfo['subject'] ?> 

Décision du : <?php echo $transactionInfo['decision_date']?> 

Transmise le :  <?php echo $transactionInfo['submission_date']?> 

Accusé reçu le :  <?php echo $transactionInfo['date'] ?> 

<?php
if($this->agent && $this->newmail == 't'){
?>
URL pour récupérer les fichiers : <?php $url = WEBSITE_SSL."/modules/actes/actes_transac_show.php?id=".$transactionInfo['transaction_id']; echo $url; ?>
<?php }?>

<?php if($transactionInfo['archive_url']) : ?>
Archive disponible sur :<?php echo $transactionInfo['archive_url']?>
<?php endif; ?> 
		<?php 
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
	
	private function getFichiers(array $transactionInfo){
		$sql = "SELECT file_path " .
			"FROM actes_transactions LEFT JOIN actes_envelopes " . 
			"ON actes_transactions.envelope_id=actes_envelopes.id " .
			"WHERE actes_transactions.id = " .$transactionInfo['id'] ;
		$result = array();
		$lesFichiers = $this->sqlQuery->query($sql);
		foreach($lesFichiers as $f){
			$result = $this->tamponnerTGZ($this->filePath . "/" . $f['file_path'],$transactionInfo);
		}
		
		$this->pdftampone = $result;
        $this->pdfgenerate = true;
		return $result;
	}
	
	private function tamponnerTGZ($filePath,$transactionInfo){
		
		$directory_unzip = $filePath."_unzip";

		if (! file_exists($directory_unzip)){
			mkdir($directory_unzip);
		}
		//FIXME beuark !
		$cmd = "tar xzf $filePath  -C ".$directory_unzip;
		Trace::wrap_exec($cmd, $status, $ret);
		chdir($directory_unzip);
		
		$result = array();
		
		$files = scandir($directory_unzip);
		foreach($files as $file){
			$path_parts = pathinfo($file);
			if ($path_parts['extension'] == 'pdf'){
				$fileString = $this->acteTamponne->tamponnerPDF($file,$transactionInfo['id']);
				file_put_contents($file,$fileString);
			}
			$result[] = $file;
		}
		return $result;
	}

	
	/**
	 * La fonction tamponnerTGZ extrait l'enveloppe tgz envoyée au MIOCT
	 * pour récupérer les pdf et les tamponner. Cette extraction n'est plus utile par 
	 * la suite. Il faut donc supprimer le dossier.
	 * @param $transactionInfo array
	 */
	private function deletedirectoryunzip($transactionInfo){
		chdir(TEDETIS_TMP_PATH);
		$sql = "SELECT file_path " .
			"FROM actes_transactions LEFT JOIN actes_envelopes " . 
			"ON actes_transactions.envelope_id=actes_envelopes.id " .
			"WHERE actes_transactions.id = " .$transactionInfo['id'] ;

		$listefichiers = $this->sqlQuery->query($sql);
		foreach($listefichiers as $f){
			$directory_unzip = $this->filePath . "/" . $f['file_path']."_unzip";
			if (file_exists($directory_unzip)){
				$cmd = "rm -r $directory_unzip";
                Trace::wrap_exec($cmd, $status, $ret);	
			}
		}
	}

}
