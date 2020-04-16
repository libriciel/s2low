<?php 

require_once __DIR__."/../../public.ssl/modules/actes/class/ActesTransaction.class.php";
require_once __DIR__ . "/IActesPdf.php";

class ActesPdf implements IActesPdf
{

    const TEXTE_NOIR = [56, 55, 55];
    const TEXTE_BLEU = [52, 60, 142];
    const BLEU_HEADER = [196, 229, 238];
    const BLEU_FONCE_BACK = [225, 241, 247];
    const BLEU_CLAIR_BACK = [245, 250, 251];

    const TAILLE_POLICE_TITRE = 18;
    const TAILLE_POLICE_COLLECTIVITE = 14;
    const TAILLE_POLICE_TITRE_PARAGRAPHE = 14;
    const TAILLE_POLICE_TABLEAU = 12;

	private $img;
	
	public function __construct(string $img) {
        $this->img = $img;
  	}

  	private function setTextColor(ExtendPdf $pdf,array $colors){
	    $pdf->SetTextColor($colors[0],$colors[1],$colors[2]);
    }

    public function initPage(ExtendPdf $pdf){
        //fini de la traitment de les requêtes.
        //créer un objet pdf.
        $pdf->AddFont('Ubuntu','R','Ubuntu-R.php');
        $pdf->AddFont('Ubuntu','B','Ubuntu-B.php');

        //ajouter une page de pdf
        $pdf->AddPage();
    }


    public function printInfosCollectivite(ExtendPdf $pdf,string $texteCollectivite, string $texteUtilisateur){
        $tailleCellInfosCollectivite = $pdf->convertPixelsToMM(self::TAILLE_POLICE_COLLECTIVITE+2);

        //ajouter collectivité et utilisateur.
        $pdf->SetFont('Ubuntu','R',self::TAILLE_POLICE_COLLECTIVITE);
        $this->setTextColor($pdf,self::TEXTE_BLEU);


        //Save the current position
        $x=$pdf->GetX();
        $y=$pdf->GetY();

        $pdf->SetLineWidth(0.5);
        $pdf->SetDrawColor(self::TEXTE_BLEU[0],self::TEXTE_BLEU[1],self::TEXTE_BLEU[2]);
        $pdf->Line(
            $x,
            $y + $pdf->convertPixelsToMM(2),
            $x,
            $y+2*$tailleCellInfosCollectivite - $pdf->convertPixelsToMM(4)
    );
        $pdf->SetLineWidth(0);

        $pdf->Cell(40,$tailleCellInfosCollectivite,"Collectivité : ".$texteCollectivite,0,0,'L');
        $pdf->Ln();
        $pdf->Cell(40,$tailleCellInfosCollectivite,"Utilisateur : ".$texteUtilisateur,0,0,'L');
        $pdf->Ln();
    }

    private function writeTitreParagraphe(ExtendPdf $pdf, string $titre){
        $this->setTextColor($pdf,self::TEXTE_NOIR);
        //obtenir tous les info et commencer de les ajouter dans tableau
        $hauteurAvantTitreParagraphe = $pdf->convertPixelsToMM(25);
        $hauteurApresTitreParagraphe = $pdf->convertPixelsToMM(9);

        $taillePolice = self::TAILLE_POLICE_TITRE_PARAGRAPHE;
        $hauteurCellPolice = $pdf->convertPixelsToMM($taillePolice)+4;

        $pdf->Ln($hauteurAvantTitreParagraphe);

        $pdf->SetFont('Ubuntu','B',$taillePolice);
        $pdf->Cell(40,$hauteurCellPolice, $titre,0,1);

        $pdf->Ln($hauteurApresTitreParagraphe);
    }

    /**
     * \brief ajouter l'entête de pdf
     * \param aucun.
     * @param ExtendPdf $pdf
     */
	public function set_head(ExtendPdf $pdf)
	{
        $pdf->Image($this->img, 10, 10, 190, 26);
        $pdf->Ln(26);

        $distanceBandeauTitre = $pdf->convertPixelsToMM(20);
        $taillePoliceTitre=self::TAILLE_POLICE_TITRE;
        $hauteurBandeauTitre = $pdf->convertPixelsToMM($taillePoliceTitre+4);
        $espaceApresTitre = $pdf->convertPixelsToMM(25);

        $pdf->Ln($distanceBandeauTitre);
		$pdf->SetFont('Ubuntu','R',$taillePoliceTitre);
        $this->setTextColor($pdf,self::TEXTE_NOIR);

        $title="Bordereau d'acquittement de transaction";
		$pdf->Cell(190,$hauteurBandeauTitre,$title,0,1,'C');
		$pdf->Ln($espaceApresTitre);
	}

	public function trans_table(ExtendPdf $pdf, array $contenuTableau)
	{
        $this->writeTitreParagraphe($pdf,"Paramètre de la transaction :");
	    $taillePolice = self::TAILLE_POLICE_TABLEAU;

        $pdf->SetMyWidths(array(80,110));
        $pdf->SetMyAligns(array('L','L'));
        $pdf->SetMyBorder(array('0','0'));

        $pdf->SetFont('Ubuntu','R',$taillePolice);

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

        foreach ($contenuTableau as $ligne){
		    $color = $colorArray[$colorIndex];
            $pdf->setMyFillcolor(array($color,$color));
            $pdf->myRow(array($ligne[0],$ligne[1]));
            $colorIndex = ($colorIndex +1) %2;
        }
	}

	public function fichier_table(ExtendPdf $pdf, $fichier_table)
	{
        $this->writeTitreParagraphe($pdf,"Fichiers contenus dans l'archive :");
        $taillePolice = self::TAILLE_POLICE_TABLEAU;

        $pdf->SetMyWidths(array(120,35,35));
        $pdf->SetMyAligns(array('L','C','C'));
        $pdf->SetMyBorder(array('0','0','0'));

        $pdf->SetFont('Ubuntu','B',$taillePolice);
        $pdf->setMyFillcolor(array(self::BLEU_HEADER,self::BLEU_HEADER,self::BLEU_HEADER));
        $pdf->myRow(array("Fichier","Type","Taille (Ko)"));

        $pdf->SetFont('Ubuntu','R',$taillePolice);
        $pdf->SetMyBorder(array('0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

        if(!is_null($fichier_table)){
            foreach ($fichier_table as $groupeFichier){
                $color = $colorArray[$colorIndex];
                $pdf->setMyFillcolor(array($color,$color,$color));
                foreach ($groupeFichier as $fileData){
                    $pdf->SetFont('Ubuntu','R',$taillePolice-2);
                    $pdf->myRow(array($fileData[0],"","" ));
                    $pdf->SetFont('Ubuntu','R',$taillePolice);
                    $pdf->myRow(array($fileData[1],$fileData[2],$fileData[3] ));

                }
                $colorIndex = ($colorIndex + 1) %2;
            }
        }
	}

	public function cycle_table(ExtendPdf $pdf, array $textes)
	{
        $this->writeTitreParagraphe($pdf,"Cycle de vie de la transaction :");

        $taillePolice = self::TAILLE_POLICE_TABLEAU;

		$pdf->SetMyWidths(array(55,65,70));
		$pdf->SetMyAligns(array('L','L','L'));
		$pdf->SetMyBorder(array('0','0','0'));
		$pdf->SetFont('Ubuntu','B',$taillePolice);

		$pdf->setMyFillcolor(array(self::BLEU_HEADER,self::BLEU_HEADER,self::BLEU_HEADER));
		$pdf->myRow(array("Etat","Date", "Message"));
		$pdf->SetFont('Ubuntu','R',$taillePolice);
		$pdf->SetMyBorder(array('0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

		foreach ($textes as $texte){
            $color = $colorArray[$colorIndex];
            $pdf->setMyFillcolor(array($color,$color,$color));
			$pdf->myRow(array($texte[0],$texte[1],$texte[2]));
            $colorIndex = ($colorIndex + 1) %2;
		}
	}
}