<?php


class ExtractDataForBordereauPDF{


    /**
     * @var TransactionSQL
     */
    private $transactionSQL;
    /**
     * @var ActesIncludedFileSQL
     */
    private $actesIncludedFileSQL;
    /**
     * @var ActesStatusSQL
     */
    private $actesStatusSQL;

    public function __construct(TransactionSQL $transactionSQL, ActesIncludedFileSQL $actesIncludedFileSQL, ActesStatusSQL $actesStatusSQL)
    {
        $this->transactionSQL = $transactionSQL;
        $this->actesIncludedFileSQL = $actesIncludedFileSQL;
        $this->actesStatusSQL = $actesStatusSQL;
    }

    /**
     * @param $transactionId
     * @param bool $addEmailNotificationField
     * @return DataForBordereauPDF
     * @throws Exception
     */

    public function extract($transactionId,$addEmailNotificationField=false){
        $data = new DataForBordereauPDF();

        $this->transactionSQL->setTransmissionId($transactionId);
        $transaction = $this->transactionSQL->getAll();
        $transactionComplement = $this->transactionSQL->getComplement($transactionId);

        $data->setDonneesTransaction($transaction, $transactionComplement);
        $data->setAddEmailNotificationField($addEmailNotificationField);

        $includedFiles = $this->actesIncludedFileSQL->getAll($transactionId);
        $data->setIncludedFiles($includedFiles);

        $workflow = $this->transactionSQL->fetchWorkflow($transactionId);
        $status = $this->actesStatusSQL->getAllStatus();
        $data->setCycleTable($workflow,$status);

        //$data->setCycleVieTransaction($workflow,$status);
        return $data;
    }
}