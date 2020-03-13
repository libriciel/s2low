<?php 

require_once __DIR__."/../../public.ssl/modules/actes/class/ActesTransaction.class.php";

class ActesPdf {

    const TEXTE_NOIR = [56, 55, 55];
    const TEXTE_BLEU = [52, 60, 142];
    const BLEU_HEADER = [196, 229, 238];
    const BLEU_FONCE_BACK = [225, 241, 247];
    const BLEU_CLAIR_BACK = [245, 250, 251];

    const TAILLE_POLICE_TITRE = 18;
    const TAILLE_POLICE_COLLECTIVITE = 14;
    const TAILLE_POLICE_TITRE_PARAGRAPHE = 14;
    const TAILLE_POLICE_TABLEAU = 12;

    /** @var DataActesPdf */
    private $data;

	/**
	 * @var ExtendPDF
	 */
	private $pdf;
	
	private $img;
	
	public function __construct() {
        $this->img = SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg";
  	}

  	public function addEmailNotificationField(){
	    $this->data->setAddEmailNotificationField(true);
  	}

  	public function setTextColor(array $colors){
	    $this->pdf->SetTextColor($colors[0],$colors[1],$colors[2]);
    }

    public function initPage(){
        //fini de la traitment de les requêtes.
        //créer un objet pdf.
        $this->pdf=new ExtendPdf();
        $this->pdf->AddFont('Ubuntu','R','Ubuntu-R.php');
        $this->pdf->AddFont('Ubuntu','B','Ubuntu-B.php');

        //ajouter une page de pdf
        $this->pdf->AddPage();
    }

    public function initData($transaction_id){
	    $this->data = new DataActesPdf($transaction_id);
        $this->initPage();
    }

    public function printInfosCollectivite(string $texteCollectivite, string $texteUtilisateur){
        $taillePoliceInfosCollectivite = 14;
        $tailleCellInfosCollectivite = $this->pdf->convertPixelsToMM($taillePoliceInfosCollectivite+2);

        //ajouter collectivité et utilisateur.
        $this->pdf->SetFont('Ubuntu','R',$taillePoliceInfosCollectivite);
        $this->setTextColor(self::TEXTE_BLEU);


        //Save the current position
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();

        $this->pdf->SetLineWidth(0.5);
        $this->pdf->SetDrawColor(self::TEXTE_BLEU[0],self::TEXTE_BLEU[1],self::TEXTE_BLEU[2]);
        $this->pdf->Line(
            $x,
            $y + $this->pdf->convertPixelsToMM(2),
            $x,
            $y+2*$tailleCellInfosCollectivite - $this->pdf->convertPixelsToMM(4)
    );
        $this->pdf->SetLineWidth(0);

        $this->pdf->Cell(40,$tailleCellInfosCollectivite,$texteCollectivite,0,0,'L');
        $this->pdf->Ln();
        $this->pdf->Cell(40,$tailleCellInfosCollectivite,$texteUtilisateur,0,0,'L');
        $this->pdf->Ln();
    }

	public function create_pdf($transaction_id) {
        $this->initData($transaction_id);
		//définir l'entête de page.
		$this->set_head();

		$this->printInfosCollectivite(
		    $this->data->getTexteCollectivite(),
            $this->data->getTexteUtilisateur());
		// imprimé la table de  transaction

        $this->writeTitreParagraphe("Paramètre de la transaction :");
		$this->trans_table(
		    $this->data->getContenuTableau());


        $this->writeTitreParagraphe("Fichiers contenus dans l'archive :");
		// imprimé la talbe de Fichier calcule dans l'archivage
		$this->fichier_table($this->data->getFichierTable());

        $this->writeTitreParagraphe("Cycle de vie de la transaction :");
		//imprimé la table de cycle
		$this->cycle_table($this->data->getCycleTable());
		
		// imprimé la notification de la transaction:
		$this->pdf->SetFont('Arial','',12);
		$this->pdf->Cell(40,10,"",0,1);
	}

	/**
	 * @param string $title le nom du fichier SANS l'extension PDF
	 * @param string $out - voir la fonction FPDF Output
	 * @return string
	 */
	public function output($title,$out = "I"){
		return $this->pdf->Output($title.".pdf",$out);
	}

    private function writeTitreParagraphe($titre){
        $this->setTextColor(self::TEXTE_NOIR);
        //obtenir tous les info et commencer de les ajouter dans tableau
        $hauteurAvantTitreParagraphe = $this->pdf->convertPixelsToMM(25);
        $hauteurApresTitreParagraphe = $this->pdf->convertPixelsToMM(9);

        $taillePolice = self::TAILLE_POLICE_TITRE_PARAGRAPHE;
        $hauteurCellPolice = $this->pdf->convertPixelsToMM($taillePolice)+4;

        $this->pdf->Ln($hauteurAvantTitreParagraphe);

        $this->pdf->SetFont('Ubuntu','B',$taillePolice);
        $this->pdf->Cell(40,$hauteurCellPolice, $titre,0,1);

        $this->pdf->Ln($hauteurApresTitreParagraphe);
    }
	/**
  * \brief ajouter l'entête de pdf
  * \param aucun.
  * 
  */
	protected function set_head()
	{
        $this->pdf->Image($this->img, 10, 10, 190, 26);
        $this->pdf->Ln(26);

        $distanceBandeauTitre = $this->pdf->convertPixelsToMM(20);
        $taillePoliceTitre=self::TAILLE_POLICE_TITRE;
        $hauteurBandeauTitre = $this->pdf->convertPixelsToMM($taillePoliceTitre+4);
        $espaceApresTitre = $this->pdf->convertPixelsToMM(25);

        $this->pdf->Ln($distanceBandeauTitre);
		$this->pdf->SetFont('Ubuntu','R',$taillePoliceTitre);
        $this->setTextColor(self::TEXTE_NOIR);

        $title="Bordereau d'acquittement de transaction";
		$this->pdf->Cell(190,$hauteurBandeauTitre,$title,0,1,'C');
		$this->pdf->Ln($espaceApresTitre);
	}

	protected function trans_table( array $contenuTableau)
	{

	    $taillePolice = self::TAILLE_POLICE_TABLEAU;
	    $tailleCellTableau=$this->pdf->convertPixelsToMM(12)+2;

        $this->pdf->SetMyWidths(array(80,110));
        $this->pdf->SetMyAligns(array('L','L'));
        $this->pdf->SetMyBorder(array('0','0'));

        $this->pdf->SetFont('Ubuntu','R',$taillePolice);

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

        foreach ($contenuTableau as $ligne){
		    $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array($color,$color));
            $this->pdf->myRow(array($ligne[0],$ligne[1]));
            $colorIndex = ($colorIndex +1) %2;
        }
	}

	public function fichier_table($fichier_table)
	{
        $taillePolice = self::TAILLE_POLICE_TABLEAU;

        $this->pdf->SetMyWidths(array(120,35,35));
        $this->pdf->SetMyAligns(array('L','C','C'));
        $this->pdf->SetMyBorder(array('0','0','0'));

        $this->pdf->SetFont('Ubuntu','B',$taillePolice);
        $this->pdf->setMyFillcolor(array(self::BLEU_HEADER,self::BLEU_HEADER,self::BLEU_HEADER));
        $this->pdf->myRow(array("Fichier","Type","Taille (Ko)"));

        $this->pdf->SetFont('Ubuntu','R',$taillePolice);
        $this->pdf->SetMyBorder(array('0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

        foreach ($fichier_table as $groupeFichier){
            $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array($color,$color,$color));
            foreach ($groupeFichier as $fileData){
                $this->pdf->SetFont('Ubuntu','R',$taillePolice-2);
                $this->pdf->myRow(array($fileData[0],"","" ));
                $this->pdf->SetFont('Ubuntu','R',$taillePolice);
                $this->pdf->myRow(array($fileData[1],$fileData[2],$fileData[3] ));

            }
            $colorIndex = ($colorIndex + 1) %2;
        }
	}

	public function cycle_table($textes)
	{

        $taillePolice = self::TAILLE_POLICE_TABLEAU;

		$this->pdf->SetMyWidths(array(55,65,70));
		$this->pdf->SetMyAligns(array('L','L','L'));
		$this->pdf->SetMyBorder(array('0','0','0'));
		$this->pdf->SetFont('Ubuntu','B',$taillePolice);

		$this->pdf->setMyFillcolor(array(self::BLEU_HEADER,self::BLEU_HEADER,self::BLEU_HEADER));
		$this->pdf->myRow(array("Etat","Date", "Message"));
		$this->pdf->SetFont('Ubuntu','R',$taillePolice);
		$this->pdf->SetMyBorder(array('0','0','0'));

        $colorIndex=0;
        $colorArray=[self::BLEU_FONCE_BACK,self::BLEU_CLAIR_BACK];

		foreach ($textes as $texte){
            $color = $colorArray[$colorIndex];
            $this->pdf->setMyFillcolor(array($color,$color,$color));
			$this->pdf->myRow(array($texte[0],$texte[1],$texte[2]));
            $colorIndex = ($colorIndex + 1) %2;
		}
	}
}