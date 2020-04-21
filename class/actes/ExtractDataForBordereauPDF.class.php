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

        $transactionComplement = $this->transactionSQL->getDonneesTransaction($transactionId);

        $data->setDonneesTransaction($transactionComplement);
        $data->setAddEmailNotificationField($addEmailNotificationField);

        $includedFiles = $this->actesIncludedFileSQL->getAll($transactionId);
        $data->setIncludedFiles($includedFiles);

        $workflow = $this->transactionSQL->fetchWorkflow($transactionId);
        $status = $this->actesStatusSQL->getAllStatus();
        $data->setCycleVieTransaction($workflow,$status);

        return $data;
    }
}