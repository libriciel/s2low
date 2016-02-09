<?php

class ActesSignature {
	
	private $actesIncludedFileSQL;
	private $actesTransactionSQL;
	private $actesEnveloppeSQL;

	public function __construct($sqlQuery){
		$this->actesIncludedFileSQL = new ActesIncludedFileSQL($sqlQuery);
		$this->actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);
		$this->actesEnveloppeSQL = new ActesEnvelopeSQL($sqlQuery);
	}
	
	public function setSignature($actes_included_file_id,$signature){
		$transaction_id = $this->actesIncludedFileSQL->getTransactionId($actes_included_file_id);
		if (! $transaction_id){
			throw new Exception("Impossible de trouver une transaction ratachée au fichier à signé");
		}
		
		$transactionInfo = $this->actesTransactionSQL->getInfo($transaction_id);
		if(! $transactionInfo){
			throw new Exception("Impossible de trouver la transaction $transaction_id");
		}
		if ($transactionInfo['last_status_id'] != 18){
			throw new Exception("La transaction $transaction_id n'est pas dans l'état « En attente d'être signée. »");
		}

		$tmpFolder = new TmpFolder();
		$tmpDir = $tmpFolder->create();
		
		$envelope_id = $transactionInfo['envelope_id'];
		
		
		$actes_envelope_info = $this->actesEnveloppeSQL->getInfo($envelope_id);
		$archivePath = ACTES_FILES_UPLOAD_ROOT.'/'.$actes_envelope_info['file_path'];
		
		$tgzExtractor = new TGZExtractor($tmpDir);
		$tgzExtractor->extract($archivePath, false);
		
		$xml_file = $this->actesIncludedFileSQL->getXMLFilename($transaction_id);
		
		$xml = simplexml_load_file($tmpDir."/".$xml_file);
		$namespaces = $xml->getDocNamespaces();
		
		$children = $xml->children($namespaces['actes']);
		$children->Document->addChild("Signature",$signature,$namespaces['actes']);
		
		$xml->asXML($tmpDir."/".$xml_file);
		
		chdir($tmpDir);
		$cmd = "tar cf - * | gzip -9 > $archivePath ";
		
		$status = system($cmd, $ret);
		
		$tmpFolder->delete($tmpDir);
		$this->actesIncludedFileSQL->setSignature($transaction_id,$actes_included_file_id,$signature);
		$this->actesTransactionSQL->updateStatus($transaction_id,1, "L'acte a été signé électroniquement");
		return $transaction_id;
	}
	
}