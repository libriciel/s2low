<?php 



$xml_data=<<<XML
<?xml version='1.0' encoding='ISO-8859-1' ?>
<actes:EnveloppeMISILLCL 
		xmlns:actes="http://www.interieur.gouv.fr/ACTES#v1.1-20040216" 
		xmlns:insee="http://xml.insee.fr/schema" 
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
		xsi:schemaLocation="http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd" >
  <actes:Emetteur>
    <actes:IDSousPref actes:Departement="001" actes:Arrondissement="1" />
  </actes:Emetteur>
  <actes:FormulairesEnvoyes>
    <actes:Formulaire>
      <actes:NomFichier>003-123456789-20110601-20110618-AI-1-2_0.xml</actes:NomFichier>
    </actes:Formulaire>
  </actes:FormulairesEnvoyes>
  <actes:Destinataire insee:SIREN="123456789" />
</actes:EnveloppeMISILLCL>
XML;

$XML2Array = new XML2Array();
print_r($XML2Array->getArray($xml_data));
//echo strval($root_children);

//$content = strval($xml->);
exit;

echo $xml;

$XML2Array = new XML2Array();
print_r($XML2Array->getArray($xml));


class XML2Array {
	
	public function getArray($xml_content){
		$root = simplexml_load_string($xml_content);		
		return  $this->getChildNode($root);
	}
	
	private function getChildNode($node){	
		$result = false;
		foreach ($node->children() as $b) {		
			$result[$b->getName()][] = $this->getChildNode($b);
		}
		if (trim(strval($node))) {
			$result['content'] = strval($node);
		}		
		return $result;
	}
	
}
