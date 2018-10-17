<?php 

require_once(SITEROOT."/public.ssl/modules/actes/class/ActesTransaction.class.php");


class ActesTransactionsXML {

	public function getTransactionFileName(array $autorityInfo, array $transactionInfo){
		return $this->getNewFileName($autorityInfo,$transactionInfo,0,"xml");
	}
	
	public function getNewFileName(array $autorityInfo, array $transactionInfo,$number,$extension){
		return $this->getFileName($autorityInfo,$transactionInfo)."_$number.$extension";
	}
	
	public function getFileName(array $autorityInfo, array $transactionInfo) {
		assert($transactionInfo["type"] != 6 );  //Dans ce cas, il faux les info de la transaction 1 correspondante
	 	
	    $name = $autorityInfo['department'] . "-" . $autorityInfo['siren'] . "-";
	    if ($transactionInfo['type'] == 7) {
			$name .= "--";
		}  else {
			$name .= date("Ymd", strtotime($transactionInfo['date_decision'] ) );
	        $name .= "-" .$transactionInfo['numero_interne'] ."-"  ;
			$nature_descr = ActesTransaction :: getTransactionNatureDescr($transactionInfo['code_nature']);
     		$name .= $nature_descr["short_descr"];
	    }
    	$name .= "-" . $transactionInfo['type'] ."-";
		switch ($transactionInfo['type']) {
      		case "1" :
      		case "6" :
			case "7" :
				$name .= "1"; break;
			case "2" : 
				$name .= "2"; break;
			case "3" : 
			case "4" :
      			$name .= "".$transactionInfo['type_reponse']; break;
		}
		return $name;
	}

	public function getXML(array $transactionInfo) {
		assert(!!$transactionInfo["date_decision"]);
		assert(!!$transactionInfo["numero_interne"]);
		assert(!!$transactionInfo["code_nature"]);
		assert(!!$transactionInfo["classification"]);
		assert(!!$transactionInfo["objet"]);
		assert(!!$transactionInfo["classification_date_version"]);
		assert(!!$transactionInfo["nom_fichier"]);
		assert(isset($transactionInfo["annexe"]));
		$xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>";
		ob_start(); ?>
		
<actes:Acte
    	xmlns:actes="http://www.interieur.gouv.fr/ACTES#v1.1-20040216"
		xmlns:insee="http://xml.insee.fr/schema"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd"
		actes:Date="<?php echo date("Y-m-d",strtotime($transactionInfo["date_decision"])) ?>"
		actes:NumeroInterne="<?php echo XML_escaping_attribute($transactionInfo["numero_interne"]) ?>"
		actes:CodeNatureActe="<?php echo XML_escaping_attribute($transactionInfo["code_nature"]) ?>" >
<?php foreach($transactionInfo["classification"] as $i => $classification) : ?>
	<actes:CodeMatiere<?php echo $i+1 ?> actes:CodeMatiere="<?php echo $classification ?>"/>
<?php endforeach;?>
	<actes:Objet><?php echo XML_escaping($transactionInfo["objet"])?></actes:Objet>
	<actes:ClassificationDateVersion><?php echo date("Y-m-d",strtotime($transactionInfo["classification_date_version"])) ?></actes:ClassificationDateVersion>
	<actes:Document>
		<actes:NomFichier><?php echo XML_escaping($transactionInfo["nom_fichier"])?></actes:NomFichier>
<?php if(isset($transactionInfo["signature"])) : ?>
		<actes:Signature><?php echo XML_escaping($transactionInfo["signature"]) ?></actes:Signature>
<?php endif;?>
	</actes:Document>
	<actes:Annexes actes:Nombre="<?php echo count($transactionInfo["annexe"]) ?>" >
<?php foreach($transactionInfo["annexe"] as $annexe) : ?>
		<actes:Annexe>
			<actes:NomFichier><?php echo XML_escaping($annexe["nom_fichier"]) ?></actes:NomFichier>
<?php if (isset($annexe["signature"])) :?>
			<actes:Signature><?php echo XML_escaping($annexe["signature"]) ?></actes:Signature>
<?php endif;?>
		</actes:Annexe>
<?php endforeach;?>
	</actes:Annexes>
</actes:Acte>
<?php 
		$xml .= ob_get_contents();
		ob_end_clean();
	
		return $xml;
	}
	
	
}