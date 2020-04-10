<?php 

require_once __DIR__."/../../public.ssl/modules/actes/class/ActesTransaction.class.php";
require_once __DIR__ . "/IActesPdf.php";

class ActesPdfLegacy implements IActesPdf
{
	/**
	 * @var ExtendPDF
	 */
	private $pdf;
	
	private $img;
	
	public function __construct() {
        $this->img = SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg";
  	}


    private function initPage(){
        //fini de la traitment de les requêtes.
        //créer un objet pdf.
        $this->pdf=new ExtendPdf();
        $this->pdf->AddFont('Ubuntu','R','Ubuntu-R.php');
        $this->pdf->AddFont('Ubuntu','B','Ubuntu-B.php');

        //ajouter une page de pdf
        $this->pdf->AddPage();
    }

    private function printInfosCollectivite(string $texteCollectivite, string $texteUtilisateur){
        $this->pdf->SetFont('Arial','B',12);
        $this->pdf->SetTextColor(94,106,23);
        $this->pdf->Cell(40,10,"Collectivité :",0,0,'R');
        $this->pdf->Cell(40,10,$texteCollectivite,0,1,'L');
        $this->pdf->Cell(40,10,"Utilisateur :",0,0,'R');
        $this->pdf->Cell(40,10,$texteUtilisateur,0,1,'L');
    }

    /**
     * @param DataForBordereauPDF $data
     * @param string $title le nom du fichier SANS l'extension PDF
     * @param string $out - voir la fonction FPDF Output
     * @return string
     */

	public function create_pdf(DataForBordereauPDF $data,string $title, string $out ="I") {

        $this->initPage();
		//définir l'entête de page.
		$this->set_head();

		$this->printInfosCollectivite(
		    $data->getTexteCollectivite(),
            $data->getTexteUtilisateur());
		// imprimé la table de  transaction

        $this->pdf->SetTextColor(40,36,94);
        $this->writeTitreParagraphe("Paramètre de la transaction :");
		$this->trans_table($data->getContenuTableau());
        $this->pdf->Cell(40,10,"",0,1);

        //$this->writeTitreParagraphe("Fichiers contenus dans l'archive :");
		// imprimé la talbe de Fichier calcule dans l'archivage
        $this->writeTitreParagraphe("Fichier contenus dans l'archive :");
		$this->fichier_table($data->getFichierTable());

        //$this->writeTitreParagraphe("Cycle de vie de la transaction :");
		//imprimé la table de cycle
        $this->pdf->Cell(40,10,"",0,1);
        $this->writeTitreParagraphe("Cycle de vie de la transaction :");
		$this->cycle_table($data->getCycleTable());
        $this->pdf->Cell(40,10,"",0,1);
		
		// imprimé la notification de la transaction:
		$this->pdf->SetFont('Arial','',12);
		$this->pdf->Cell(40,10,"",0,1);

        return $this->pdf->Output($title.".pdf",$out);
	}

    /**
     * @param $titre
     * @return string
     */

    private function writeTitreParagraphe($titre){
        $this->pdf->SetFont('Arial','',12);
        $this->myRectangle(60);
        $this->pdf->Cell(40,10,$titre,0,1);
    }
	/**
  * \brief ajouter l'entête de pdf
  * \param aucun.
  * 
  */
	private function set_head()
	{
        $title="BORDEREAU D'ACQUITTEMENT DE TRANSACTION";
        $this->pdf->Image($this->img, 10, 10, 190, 26);

        $this->pdf->Ln(40);
        $this->pdf->SetFont('Arial','B',16);
        $this->pdf->Cell(20);
        //$this->pdf->SetFillColor(140,207,247);

        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->SetFillColor(192);
        $this->pdf->RoundedRect($x-2, $y-1, 150, 8, 3, 'DF', '13');
        $this->pdf->Cell(140,6,$title,0,1);
        $this->pdf->Ln(7);

	}

	private function trans_table( array $contenuTableau)
	{
        //obtenir tous les info et commencer de les ajouter dans tableau
        $this->pdf->SetFont('Arial','i',10);
        $this->pdf->SetMyWidths(array(10,70,80));
        $this->pdf->SetMyAligns(array('0','L','L'));
        $this->pdf->SetMyBorder(array('0','BT','BT'));
        $this->pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254)));
        foreach ($contenuTableau as $ligne){
            $this->pdf->myRow(array("",$ligne[0],$ligne[1]));
        }
	}

	private function fichier_table($fichier_table)
	{
        //obtenir tous les info et commencer de les ajouter dans tableau
        $this->pdf->SetMyWidths(array(2,100,40,50));
        $this->pdf->SetMyAligns(array('C','C','C','C'));
        $this->pdf->SetMyBorder(array('0','R','RL','L'));
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->setMyFillcolor(array(array(255,255,255),array(200,220,255),array(200,220,255),array(200,220,255)));
        $this->pdf->myRow(array("","Fichier","Type de fichier","Taille du fichier"));
        $this->pdf->SetFont('Arial','i',10);
        $this->pdf->SetMyBorder(array('R','1','1','1'));
        $this->pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254),array(216,252,254)));
        foreach ($fichier_table as $groupeFichier)
        {
            foreach ($groupeFichier as $fileData)
                {
                    $this->pdf->SetMyBorder(array('R','LTR','LTR','LTR'));
                    $this->pdf->myRow(array("",$fileData[0],"","" ));
                    $this->pdf->SetMyBorder(array('R','LBR','LBR','LBR'));
                    $this->pdf->myRow(array("",$fileData[1],$fileData[2],$fileData[3]));
                }
        }
	}

	private function cycle_table($textes)
	{
        $this->pdf->SetMyWidths(array(10,50,60,60));
        $this->pdf->SetMyAligns(array('C','C','C','C'));
        $this->pdf->SetMyBorder(array('0','R','RL','L'));
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->setMyFillcolor(array(array(255,255,255),array(200,220,255),array(200,220,255),array(200,220,255)));
        $this->pdf->myRow(array("","Etat","Date", "Message"));
        $this->pdf->SetFont('Arial','i',10);
        $this->pdf->SetMyBorder(array('R','1','1','1'));
        $this->pdf->setMyFillcolor(array(array(255,255,255),array(216,252,254),array(216,252,254),array(216,252,254)));
        foreach ($textes as $texte)
        {
            $this->pdf->myRow(array("",$texte[0],$texte[1],$texte[2]));
        }
	}
    private function 	myRectangle($w,$h=6)
    {
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->SetFillColor(192);

        // pour changer le style, voir le commentaire de la fonction rounderect ExtendPdf::RoundeRect()
        $this->pdf->RoundedRect($x-2, $y+2, $w, $h, 3, 'DF', '13');
    }
}