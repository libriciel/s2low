<?php

class ActeTamponne {

	/** @var  ActesTransactionsSQL */
	private $actesTransactionsSQL;

	public function __construct(ActesTransactionsSQL $actesTransactionsSQL) {
		$this->actesTransactionsSQL = $actesTransactionsSQL;
	}


	public function tamponnerPDF($file_path,$transaction_id,$date_affichage = false){

		$transactionInfo = $this->actesTransactionsSQL->getDateTampon($transaction_id);

		set_include_path(SITEROOT."/ext/" . PATH_SEPARATOR .   get_include_path());
		require_once(SITEROOT."/class/TamponPDF.class.php");

		$pdftkise='/tmp/modif_' .basename($file_path);
		$cmdpdftk='timeout 10 pdftk '.$file_path." stamp ".SITEROOT."/data-exemple/vide.pdf output ".$pdftkise;
		$status='';
		$ret='';
		@ Trace::wrap_exec($cmdpdftk, $status, $ret);
		if ($status === false || $ret != 0) {
			$cmdpdftk='timeout 10 pdfsam-console -f '.$file_path." -o ".$pdftkise ." concat";
			@ Trace::wrap_exec($cmdpdftk, $status, $ret);
			if ($status === false || $ret != 0) {
				$pdftkise=$file_path;
			}
		}

		try {
			$pdf = Zend_Pdf::load($pdftkise);
		} catch (Exception $e){
			return file_get_contents($pdftkise);
		}
		$tampon = new TamponPDF($pdf);
		if ($date_affichage) {
			$date_affichage = date("d/m/Y", strtotime($date_affichage));
		}
		$tampon->setText(array("Envoyé en préfecture le ".date("d/m/Y",strtotime($transactionInfo['submission_date'])),
			"Reçu en préfecture le ".date("d/m/Y",strtotime($transactionInfo['date'])),
			"Affiché le ".$date_affichage ,
			"ID : ".$transactionInfo['unique_id']));
		try {
			$txt =  $tampon->getFileAsString();
		} catch (Exception $e){
			return file_get_contents($pdftkise);
		}
		return $txt;
	}

	public function render($file_path,$transaction_id, $date_affichage = false){
		$content = $this->tamponnerPDF($file_path,$transaction_id,$date_affichage);
		$filename = basename($file_path);
		header('Content-type: application/pdf');
		header("Content-Disposition: attachment; filename=$filename");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
		echo $content;
	}


}