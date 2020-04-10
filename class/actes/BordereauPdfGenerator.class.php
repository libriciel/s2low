<?php




class BordereauPdfGenerator
{
    /**
     * @var ExtractDataForBordereauPDF
     */
    private $extractDataForBordereauPDF;
    /**
     * @var IActesPdf
     */
    private $actesPdf;

    public function __construct(ExtractDataForBordereauPDF $extractDataForBordereauPDF, IActesPdf $actesPdf)
    {
        $this->extractDataForBordereauPDF= $extractDataForBordereauPDF;
        $this->actesPdf = $actesPdf;
    }
    public function generate($transactionId,$output,$addEmailNotificationField,$out="I"){
        $data = $this->extractDataForBordereauPDF->extract($transactionId);
        $pdf = $this->actesPdf->create_pdf($data,$output,$out);
        return $pdf;
    }
}