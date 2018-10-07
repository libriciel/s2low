<?php

require_once __DIR__."/../ext/FPDF.class.php";

class ExtendPdf extends FPDF {

	public $widths;
	public $aligns;
	public $fillcolor;
	public $border;
	
  /**
  * \brief Initialiser l'entête du fichier pdf.
  * \param pas de paramètre.
  * 
  */
	public function Header() {
		//Select Arial bold 15
		$this->SetFont('Arial','',6);
		//Move to the right
		$this->SetXY(2, 3);
		$this->Cell(120);

		//Framed title
		$this->Cell(30,6,OPERATEUR_DE_TELETRANSMISSION);
		//Line break
		$this->Ln(6);
		  $this->Line(10, 6, 120, 6);
	}
	
  /**
  * \brief Initialiser le pied du fichier pdf.
  * \param pas de paramètre.
  * 
  */
	public function Footer() {
	    //Go to 1.5 cm from bottom
	    $this->SetY(-15);
	    //Select Arial italic 8
	    $this->SetFont('Arial','I',8);
	    //Print centered page number
	    $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
	}

	/**
  * @brief Initialiser largeur du collone.
  * @param array $w  => largeur de chaque collone si on as plusieurs.
  * 
  */
	public function SetMyWidths($w) {
	    //Set the array of column widths
	    $this->widths=$w;
	}

	/**
  * @brief Initialiser alligne du chaque collone.
  * @param $a =array =>la façcon d'alligne de chaque multicell.
  * 
  */
	public function SetMyAligns($a) {
	    //Set the array of column alignments
	    $this->aligns=$a;
	}
	
	/**
  * @brief Initialiser border du chaque multicell.
  * @param $border =array =>la border de chaque multicell.
  *      par défault, $border=0 =>pas de border.
  *      $border peut prend ses valeur de 'T','B','R','L' ou le metter ensemble.
  *      T=top, B=Bottom, R=right, L=left.
  */
	public function setMyBorder($border=0) {
		$this->border=$border;
	}
	
	 /**
  * @brief Initialiser coleur de celle.
  * @param $fillcolor =array =>la colleur remplie dans un multi cell.
  * 
  */
	public function setMyFillcolor($fillcolor) {
		$this->fillcolor=$fillcolor;
	}

	/**
	* @brief contruir le table ligne par ligne
	* @param $data =array =>l'info qui va remplir dans les multicell.
	*/
	public function myRow($data) {
	    //Calculate the height of the row
	    $nb=0;
	    for($i=0;$i<count($data);$i++)
	        $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
	    $h=5*$nb;
	    //Issue a page break first if needed
	    $this->CheckPageBreak($h);
	    //Draw the cells of the row
	    for($i=0;$i<count($data);$i++)
	    {
	        $w=$this->widths[$i];
	        
	        //default aligne à  gauche.
	        $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
	        //default pas de border
	        $b=isset($this->border[$i]) ? $this->border[$i] : 0;
	        
	        //default pas de fill color. $fillcolor=0~255 gray value.
	        $fc=isset($this->fillcolor[$i]) ? $this->fillcolor[$i]: array(255,255,255);
	        	        
	        //Save the current position
	        $x=$this->GetX();
	        $y=$this->GetY();
	        //Draw the border
	        $this->Rect($x,$y,$w,$h);
	        //Print the text
	        $this->SetFillColor($fc[0],$fc[1],$fc[2]);
	        //default on fill cette cell avec le fillcolor, fillcolor default =255.
	        //$this->MultiCell($w,5,$fc[0].'-'.$fc[1].'-'.$fc[2],$b,$a,1);
	       	$this->MultiCell($w,5,$data[$i],$b,$a,1);
	      	
	        //Put the position to the right of the cell
	        $this->SetXY($x+$w,$y);
	    }
	    //Go to the next line
	    $this->Ln($h);
	}

	/**
  * @brief véréfier le table est deborder une page ou non.
  * @param $h =float =>la position de ligne
  *     si il a déborder, créer une nouvelle page.
  * 
  */
	public function CheckPageBreak($h) {
	    //If the height h would cause an overflow, add a new page immediately
	    if($this->GetY()+$h>$this->PageBreakTrigger)
	        $this->AddPage($this->CurOrientation);
	}
	
  /**
  * @brief  calculer on doit sauter des ligne ou pas si le text est déborder un multicell
  * @param  $w =float =>large de multicell
  * @param  $txt=string => le contenu de ce multicell
   * @return int
  * 
  */
	public function NbLines($w,$txt) {
	    //Computes the number of lines a MultiCell of width w will take
	    $cw=&$this->CurrentFont['cw'];
	    if($w==0)
	        $w=$this->w-$this->rMargin-$this->x;
	    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	    $s=str_replace("\r",'',$txt);
	    $nb=strlen($s);
	    if($nb>0 and $s[$nb-1]=="\n")
	        $nb--;
	    $sep=-1;
	    $i=0;
	    $j=0;
	    $l=0;
	    $nl=1;
	    while($i<$nb)
	    {
	        $c=$s[$i];
	        if($c=="\n")
	        {
	            $i++;
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	            continue;
	        }
	        if($c==' ')
	            $sep=$i;
	        $l+=$cw[$c];
	        if($l>$wmax)
	        {
	            if($sep==-1)
	            {
	                if($i==$j)
	                    $i++;
	            }
	            else
	                $i=$sep+1;
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	        }
	        else
	            $i++;
	    }
	    return $nl;
	}

	/**
  * @brief affichier un effet de rectangle avec le coin rond
  * @param $x,
	 * @param $y =float =>la position de cette rectangle (le point de gauche en haut.)
  * @param $w,
	 * @param $h =float => le large et le hauteur de rectangle.
	 * @param $r
  * @param $style
  * @param $angle= 1, 2 ,3, 4, ou mélanger les pour définir le quelle angle est rond.
  */
	function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234') {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' or $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);

        $this->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));

        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));
        if (strpos($angle, '2')===false)
            $this->_out(sprintf('%.2f %.2f l', ($x+$w)*$k,($hp-$y)*$k ));
        else
            $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);

        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
        if (strpos($angle, '3')===false)
            $this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-($y+$h))*$k));
        else
            $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);

        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
        if (strpos($angle, '4')===false)
            $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-($y+$h))*$k));
        else
            $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);

        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
        if (strpos($angle, '1')===false)
        {
            $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$y)*$k ));
            $this->_out(sprintf('%.2f %.2f l',($x+$r)*$k,($hp-$y)*$k ));
        }
        else
            $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }
    
    public function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
}

