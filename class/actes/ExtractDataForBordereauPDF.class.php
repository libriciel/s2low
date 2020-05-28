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

    public function __construct(TransactionSQL $transactionSQL,
                                ActesIncludedFileSQL $actesIncludedFileSQL,
                                ActesStatusSQL $actesStatusSQL,
                                ActesTypePJSQL $actesTypePJSQL)
    {
        $this->transactionSQL = $transactionSQL;
        $this->actesIncludedFileSQL = $actesIncludedFileSQL;
        $this->actesStatusSQL = $actesStatusSQL;
        $this->actesTypePJSQL= $actesTypePJSQL;
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
        //var_dump($transactionComplement);
        //die();
        $data->setDonneesTransaction($transactionComplement);
        $data->setAddEmailNotificationField($addEmailNotificationField);

        $includedFiles = $this->actesIncludedFileSQL->getAll($transactionId);

        foreach($includedFiles as $index => $file){
            $includedFiles[$index]["typeDocument"]='Enveloppe métier';   //TODO : vérifier

            if($file['code_pj']){
                $libelle = $this->actesTypePJSQL->getLibelle($file['code_pj'])?:$file['code_pj'];
                $typeDocument = "Annexe";
                if($file['code_pj'] === $this->actesTypePJSQL->getDefaultType($transactionComplement["nature_code"])){
                    $typeDocument = "Document principal";
                }
            $includedFiles[$index]["typeDocument"]="$typeDocument ($libelle)";
            }
        }
        $data->setIncludedFiles($includedFiles);

        $workflow = $this->transactionSQL->fetchWorkflow($transactionId);
        $status = $this->actesStatusSQL->getAllStatus();
        $data->setCycleVieTransaction($workflow,$status);

        return $data;
    }
}