<?php 
class ActesEnveloppeXML {

	private $returnMail;
	
	public function getName($appli_name,$siren,$serial){
		$date = date('Ymd');
		return  "$appli_name--$siren--$date-$serial.xml";
	}

	private function addReturnMail($mail){
		if ($mail && ! in_array($mail,$this->returnMail)){
			$this->returnMail[] = $mail;
		}
	}
	
	public function getEnveloppe(array $authorityInfo,array $userInfo,array $transactionFileName) {
		$this->returnMail =array();
		$this->addReturnMail(ACTES_TDT_MAIL_ADDRESS);
		$this->addReturnMail($userInfo["email"]);
		$this->addReturnMail($authorityInfo["email"]);
	  	
	  	
	  	$xml = '<?xml version="1.0" encoding="ISO-8859-1" ?>';
	  	ob_start();
	  	?>

<actes:EnveloppeCLMISILL 	xmlns:actes="http://www.interieur.gouv.fr/ACTES#v1.1-20040216" 
							xmlns:insee="http://xml.insee.fr/schema" 
							xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
							xsi:schemaLocation="http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd">
	<actes:Emetteur>
		<actes:IDCL insee:SIREN="<?php echo $authorityInfo["siren"] ?>"
					actes:Departement="<?php echo  $authorityInfo["department"]?>"
					actes:Arrondissement="<?php echo $authorityInfo["district"] ?>"
	     			actes:Nature="<?php echo $authorityInfo["authority_type_id"] ?>" />
		<actes:Referent>
			<actes:Nom><?php echo XML_escaping($userInfo["pretty_name"])?></actes:Nom>
			<actes:Telephone><?php echo XML_escaping($userInfo["telephone"]?$userInfo["telephone"]:$authorityInfo["telephone"]) ?></actes:Telephone>
			<actes:Email><?php echo XML_escaping($userInfo["email"]) ?></actes:Email>
		</actes:Referent>
	</actes:Emetteur>
	<actes:AdressesRetour>
<?php  foreach ($this->returnMail as $email) : ?>
		<actes:Email><?php echo XML_escaping($email) ?></actes:Email>
<?php endforeach;?>
	</actes:AdressesRetour>
	<actes:FormulairesEnvoyes>
<?php  foreach ($transactionFileName as $transac) : ?>
		<actes:Formulaire>
			<actes:NomFichier><?php echo XML_escaping($transac) ?></actes:NomFichier>
		</actes:Formulaire>
<?php endforeach;?>
	</actes:FormulairesEnvoyes>
</actes:EnveloppeCLMISILL>
<?php 
		$xml .= ob_get_contents();
		ob_end_clean();
	
		return $xml;
	  }

	public function getInfo($xml_content){
		
		$XML2Array = new XML2Array();
		$XML2Array->setUniqueNode(array('IDSGAR','IDPref','IDSousPref','Destinataire','IDCL','Emetteur','AdressesRetour','Formulaire','Nom','Telephone','NomFichier'));
		$XML2Array->setNode2Remonte(array('Formulaire'));
		$result = $XML2Array->getArray($xml_content);
		
		unset($result['schemaLocation']);
		foreach(array('IDSGAR','IDPref','IDSousPref') as $type)
		if (isset($result['Emetteur'][$type])){
			$result['Emetteur']= $result['Emetteur'][$type];
			$result['Emetteur']['type'] = $type;			
		}
		if (isset($result['Destinataire'])){
			$result['Destinataire'] = $result['Destinataire']['SIREN'];
		}

		return $result;	
	}

}