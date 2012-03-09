<?php 
require_once(dirname(__FILE__)."/../../../../class/Database.class.php");
require_once(dirname(__FILE__)."/../../../../class/Mailer.class.php");

require_once("ActesPdf.class.php");

class ActesNotification {
	
	private $db;
	private $lesFichiers;
	private $filePath;
	
	public function __construct(Database $db){
		$this->db = $db;
		$this->lesFichiers = array();
		$this->setFilePath(ACTES_FILES_UPLOAD_ROOT);
	}
	
	public function setFilePath($filePath){
		$this->filePath = $filePath;
	}
	
	public function sendAutomaticNotification(){
		$sql = "SELECT actes_transactions.id  FROM actes_transactions ".
				" WHERE last_status_id = 4  AND auto_broadcasted=false ";
		
		$result = $this->db->select($sql);

		while ($row = $result->get_next_row()){
			echo "Notification de la transaction {$row['id']}\n";
			$this->sendNotificationManuel($row['id']);
		}
	}
	
	public function sendNotificationManuel($transactionId){
		$transactionInfo = $this->getTransactionInfo($transactionId);
		$this->sendNotification($transactionInfo);
		$this->deletedirectoryunzip($transactionInfo);
	}
	
	private function sendNotification(array $transactionInfo){
		$defaultBroadcastEmail =explode(',',$transactionInfo['default_broadcast_email']);
		$brodcastEmail =explode(',',$transactionInfo['broadcast_emails']);
		$brodcastEmail = array_diff($brodcastEmail,$defaultBroadcastEmail);
		
		if ($transactionInfo['auto_broadcasted'] == 'f'){
			//envoie du mail au proprietaire de l'acte
			$this->sendMail($transactionInfo,$transactionInfo['email'],true);
			//envoie du mail a toutes les adresses renseignees dans defaut
            foreach($defaultBroadcastEmail as $email){              
            	$this->sendMail($transactionInfo,$email,true);
            }
			$this->setAutoBroadcasted($transactionInfo['transaction_id']);
		}
		if ($transactionInfo['broadcast_emails']){
            foreach($brodcastEmail as $email){
            	$this->sendMail($transactionInfo,$email,$transactionInfo['broadcast_send_sources'] == 1);
            }
			$this->setBroadcasted($transactionInfo['transaction_id']);
		}
	}
	
	private function sendMail($transactionInfo,$emails,$withFile){
		if (! $emails){
			return;
		}
		$mailContent = $this->getMailContent($transactionInfo);
		$lesFichiers = $this->getFichiers($transactionInfo);	
	
		$mailer = new Mailer();
				
		$err = $mailer->addRecipient($emails);
		if (! $err){
			echo "$emails invalide ! \n";
			return;
		}
		if ($withFile){
			foreach ($lesFichiers as $fichier){
				$mailer->addFile($fichier);
			}
		}
		
		$trans = new ActesTransaction();
		$trans->setId($transactionInfo['transaction_id']);
		$trans->init();
		
		$envelope = new ActesEnvelope($trans->get("envelope_id"));
		$envelope->init();

		$owner = new User($envelope->get("user_id"));
		$owner->init();
		$pdf=new ActesPdf($trans,$owner);	
		$pdf->addEmailNotificationField();
		$pdf->create_pdf();
		$monpdf = $pdf->output("bordereau_acquittement.pdf","S");
		$mailer->addStringAsFile("bordereau_acquittement.pdf",$monpdf);
		
		$mailer->sendMail("[{$transactionInfo['name']}] Notification d'accusé de réception pour l'acte " . $transactionInfo['number'] , $mailContent);
	}
	
	public function getTransactionInfo($transactionId){
		$sql = "SELECT actes_transactions.id as transaction_id, actes_transactions.number, actes_transactions.subject," .
				"actes_transactions.decision_date, actes_transactions.unique_id, " .
				"actes_envelopes.submission_date, actes_transactions_workflow.date, " .
				"actes_transactions.broadcast_emails, actes_transactions.archive_url, " .
				"actes_transactions.broadcast_send_sources, authorities.default_broadcast_email, " .
				" actes_transactions.auto_broadcasted,authorities.name, actes_envelopes.email " .
				"FROM actes_transactions, actes_envelopes, authorities, actes_transactions_workflow " .
				" WHERE actes_transactions.envelope_id = actes_envelopes.id " .
				" AND authorities.siren = actes_envelopes.siren" .
				"  AND actes_transactions.id = " . $transactionId . 
				"  AND actes_transactions_workflow.transaction_id = " . $transactionId . 
				"  AND actes_transactions.last_status_id = 4 ";

		return $this->db->getOneLine($sql);
	}
	
	private function getMailContent($transactionInfo){
		ob_start();?>
L'acte de référence interne <?php echo $transactionInfo['number'] ?> a été acquitté sous l'identifiant unique <?php echo $transactionInfo['unique_id']  ?>

Object : <?php echo $transactionInfo['subject'] ?> 

Décision du : <?php echo $transactionInfo['decision_date']?> 

Transmise le :  <?php echo $transactionInfo['submission_date']?> 

Accusé reçu le :  <?php echo $transactionInfo['date'] ?> 

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
			"WHERE actes_transactions.id = " .$transactionInfo['transaction_id'] ;
		$result = array();
		$lesFichiers = $this->db->fetchAll($sql);
		foreach($lesFichiers as $f){
			$result = $this->tamponnerTGZ($this->filePath . "/" . $f['file_path'],$transactionInfo);
		}
		
		
		return $result;
	}
	
	private function tamponnerTGZ($filePath,$transactionInfo){
		
		$directory_unzip = $filePath."_unzip";
		//$filePath_tampon = dirname($filePath)."/".basename($filePath,"tar.gz")."_tampon.tar.gz";
		
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
				$fileString = $this->tamponnerPDF($file,$transactionInfo);
				file_put_contents($file,$fileString);
			}
			$result[] = $file;
		}
		
		/*$cmd = "tar czf ".$filePath_tampon." * ";
		Trace::wrap_exec($cmd, $status, $ret);
		*/
		return $result;
	}
	
	
	private function tamponnerPDF($file,$transactionInfo){
		
		set_include_path(SITEROOT."/ext/" . PATH_SEPARATOR .   get_include_path());
		require_once(SITEROOT."/class/TamponPDF.class.php");
	
		try {	
			$pdf = Zend_Pdf::load($file);
		} catch (Exception $e){
			return file_get_contents($file);
		}
		$tampon = new TamponPDF($pdf);
		$tampon->setText(array("Envoyé en préfecture le ".date("d/m/Y",strtotime($transactionInfo['submission_date'])),
		"Reçu en préfecture le ".date("d/m/Y",strtotime($transactionInfo['date'])),
		"Affiché le " ));
		try {
			$txt =  $tampon->getFileAsString();
		} catch (Exception $e){
			
			return file_get_contents($file);
		}
		return $txt; 
	}
	
	private function setBroadcasted($transactionId) {
		$sql = "UPDATE actes_transactions SET broadcasted = TRUE " .
					" WHERE actes_transactions.id = " . $transactionId;
		$this->db->exec($sql);
	}
	
	private function setAutoBroadcasted($transactionId){
		$sql = "UPDATE actes_transactions SET auto_broadcasted = TRUE " .
					" WHERE actes_transactions.id = " . $transactionId;
		$this->db->exec($sql);
	}
	
	/**
	 * La fonction tamponnerTGZ extrait l'enveloppe tgz envoyée au MIOCT
	 * pour récupérer les pdf et les tamponner. Cette extraction n'est plus utile par 
	 * la suite. Il faut donc supprimer le dossier. 
	 */
	private function deletedirectoryunzip($transactionInfo){
		$sql = "SELECT file_path " .
			"FROM actes_transactions LEFT JOIN actes_envelopes " . 
			"ON actes_transactions.envelope_id=actes_envelopes.id " .
			"WHERE actes_transactions.id = " .$transactionInfo['transaction_id'] ;
		$listefichiers=array();
		$listefichiers = $this->db->fetchAll($sql);
		foreach($listefichiers as $f){
			$directory_unzip = $this->filePath . "/" . $f['file_path']."_unzip";
			if (file_exists($directory_unzip)){
				$cmd = "rm -r $directory_unzip";
                Trace::wrap_exec($cmd, $status, $ret);	
			}
		}
	}
}
