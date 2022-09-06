<?php

namespace S2lowLegacy\Class\actes;

use Exception;

class ExtractDataForBordereauPDF
{
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
    /**
     * @var ActesTypePJSQL
     */
    private $actesTypePJSQL;

    public function __construct(
        TransactionSQL $transactionSQL,
        ActesIncludedFileSQL $actesIncludedFileSQL,
        ActesStatusSQL $actesStatusSQL,
        ActesTypePJSQL $actesTypePJSQL
    ) {
        $this->transactionSQL = $transactionSQL;
        $this->actesIncludedFileSQL = $actesIncludedFileSQL;
        $this->actesStatusSQL = $actesStatusSQL;
        $this->actesTypePJSQL = $actesTypePJSQL;
    }

    /**
     * @param $transactionId
     * @param bool $addEmailNotificationField
     * @return DataForBordereauPDF
     * @throws Exception
     */

    public function extract($transactionId, $addEmailNotificationField = false)
    {
        $data = new DataForBordereauPDF();

        $transactionComplement = $this->transactionSQL->getDonneesTransaction($transactionId);
        $data->setDonneesTransaction($transactionComplement);
        $data->setAddEmailNotificationField($addEmailNotificationField);

        $includedFiles = $this->actesIncludedFileSQL->getAll($transactionId);

        foreach ($includedFiles as $index => $file) {
            $includedFiles[$index]["typeDocument"] = 'Enveloppe métier';

            if ($file['code_pj']) {
                $libelle = $this->actesTypePJSQL->getLibelle($file['code_pj']) ?: $file['code_pj'];
                $typeDocument = $this->actesTypePJSQL->getTypeDocument(
                    $file["code_pj"],
                    $transactionComplement["nature_code"]
                );
                $includedFiles[$index]["typeDocument"] = "$typeDocument ($libelle)";
            }
        }
        $data->setIncludedFiles($includedFiles);

        $workflow = $this->transactionSQL->fetchWorkflow($transactionId);
        $status = $this->actesStatusSQL->getAllStatus();
        $data->setCycleVieTransaction($workflow, $status);

        return $data;
    }
}
