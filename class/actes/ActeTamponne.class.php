<?php

class ActeTamponne {

	/** @var  ActesTransactionsSQL */
	private $actesTransactionsSQL;

	/** @var  PDFStampWrapper */
	private $pdfStampWrapper;

	/** @var S2lowLogger */
	private $logger;

	public function __construct(
	    ActesTransactionsSQL $actesTransactionsSQL,
        PDFStampWrapper $pdfStampWrapper,
        S2lowLogger $logger
    ) {
		$this->actesTransactionsSQL = $actesTransactionsSQL;
		$this->pdfStampWrapper = $pdfStampWrapper;
		$this->logger = $logger;
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
            $result =  $this->pdfStampWrapper->stamp($file_path, $pdfStampData);
            $this->logger->info("Tamponnage de l'acte $transaction_id");
            return $result;
        } catch (Exception $e){
            $this->logger->error("Impossible de tamponné l'acte $transaction_id : " . $e->getMessage());
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