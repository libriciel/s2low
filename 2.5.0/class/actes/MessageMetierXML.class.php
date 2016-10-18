<?php 

class MessageMetierXML {
	
	private $namespaces;
	
	public function getInfo($xml_content){
				
		
		
		$XML2Array = new XML2Array();
		$XML2Array->setUniqueNode(array('CodeMatiere1',
										'CodeMatiere2',
										'CodeMatiere3',
										'CodeMatiere4',
										'CodeMatiere5',	
										'NomFichier',
										'Annexes',
										'Document',
										'Motif',
										'InfosCourrierPref',
										'Objet',
										'ClassificationDateVersion',
										'PiecesJointes',
										'NatureIllegalite',
										'DateDepot',
										'DateClassification',
										));
		$XML2Array->setNode2Remonte(array('CodeMatiere','Annexe'));
		$result = $XML2Array->getArray($xml_content);
		
		unset($result['schemaLocation']);
		
		return $result;
	}
	
	
}
