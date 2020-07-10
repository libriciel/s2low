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
        $data = $this->extractDataForBordereauPDF->extract($transactionId,$addEmailNotificationField);

        $legacy = get_class($this->actesPdf)=== ActesPdfLegacy::class;

        $pdf=new ExtendPdf($legacy);

        $this->create_pdf($pdf,$data);
        return $pdf->Output($output,$out);
    }

    /**
     * @param ExtendPdf $pdf
     * @param DataForBordereauPDF $data
     */

    public function create_pdf(ExtendPdf $pdf, DataForBordereauPDF $data) {

        $this->actesPdf->initPage($pdf);
        //définir l'entête de page.
        $this->actesPdf->setHead($pdf);

        $this->actesPdf->printInfosCollectivite(
            $pdf,
            $data->getTexteCollectivite(),
            $data->getTexteUtilisateur());
        // imprimé la table de  transaction

        $this->actesPdf->transTable(
            $pdf,
            $data->getContenuTableau());

        //$this->writeTitreParagraphe("Fichiers contenus dans l'archive :");
        // imprimé la talbe de Fichier calcule dans l'archivage
        $this->actesPdf->fichierTable(
            $pdf,
            $data->getFichierTable());

        //$this->writeTitreParagraphe("Cycle de vie de la transaction :");
        //imprimé la table de cycle
        $this->actesPdf->cycleTable(
            $pdf,
            $data->getCycleTable());

        // imprimé la notification de la transaction:
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(40,10,"",0,1);
    }
}