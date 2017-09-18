<?php

class ActeTamponne {

	/** @var  ActesTransactionsSQL */
	private $actesTransactionsSQL;

	/** @var  PDFStampWrapper */
	private $pdfStampWrapper;

	public function __construct(
	    ActesTransactionsSQL $actesTransactionsSQL,
        PDFStampWrapper $pdfStampWrapper
    ) {
		$this->actesTransactionsSQL = $actesTransactionsSQL;
		$this->pdfStampWrapper = $pdfStampWrapper;
	}

	public function tamponnerPDF($file_path,$transaction_id,$date_affichage = false){

		$transactionInfo = $this->actesTransactionsSQL->getDateTampon($transaction_id);

        $date_reception = $transactionInfo['date'];

        $actesTransactionsStatusInfo = $this->actesTransactionsSQL->getStatusInfo($transaction_id,4);

        $arActes = $actesTransactionsStatusInfo['flux_retour'];

        $xml = simplexml_load_string($arActes);

        if ($xml && $xml->asXML()){
            $date_reception = strval($xml->attributes("http://www.interieur.gouv.fr/ACTES#v1.1-20040216")->DateReception);
        }

        $pdfStampData = new PDFStampData();
        $pdfStampData->envoi_prefecture_date = $transactionInfo['submission_date'];
        $pdfStampData->recu_prefecture_date = $date_reception;
        $pdfStampData->affichage_date = $date_affichage;
        $pdfStampData->identifiant_unique = $transactionInfo['unique_id'];

        try {
            return $this->pdfStampWrapper->stamp($file_path, $pdfStampData);
        } catch (Exception $e){
            return file_get_contents($file_path);
        }
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