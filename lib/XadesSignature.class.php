<?php

//http://users.dcc.uchile.cl/~pcamacho/tutorial/web/xmlsec/xmlsec.html
class XadesSignature {

	public function sign($xml_file_to_sign,$p12_certificate_path,$p12_password, $xml_file_signed){
		libxml_use_internal_errors(true);

		$domDocument = new DOMDocument();
		$result = $domDocument->load($xml_file_to_sign, LIBXML_PARSEHUGE);
		if (! $result){
			$errors = libxml_get_errors();
			throw new Exception($errors[0]->message);
		}

		if (! $domDocument->documentElement->attributes->getNamedItem('Id')){
			throw new Exception("Le document XML ne contient pas d'Id");
		}
		$id = $domDocument->documentElement->attributes->getNamedItem('Id')->nodeValue;


		$signatureTemplate = $this->getXMLSignatureTemplate();

		$child  = $signatureTemplate->children("http://www.w3.org/2000/09/xmldsig#");
		$child->SignedInfo->Reference["URI"] = "#$id";

		$signatureTemplateDOM = dom_import_simplexml($signatureTemplate);

		$rootName = $domDocument->documentElement->nodeName;

		echo $rootName;


		//TODO Vérifier avec un $pes_aller_id contenant des caractère de controle XML

		//TODO Ajouter les champs xades
		//TODO CertDigest : openssl x509 -in ../../certificat/user.crt -outform der | openssl sha1 -binary | openssl base64


		$node = $domDocument->importNode($signatureTemplateDOM, true);
		$domDocument->documentElement->appendChild($node);

		$tmp_file = sys_get_temp_dir()."/".uniqid("xmlsigtmp");
		$domDocument->save($tmp_file);


		$command = "xmlsec1 --sign --id-attr:Id toto --output $xml_file_signed --pkcs12 $p12_certificate_path --pwd $p12_password $tmp_file 2>&1";
		exec($command,$output,$return_var);
		unlink($tmp_file);
		if ($return_var != 0){
			throw new Exception("Erreur ($return_var) lors de la signature technique : ".implode("\n",$output));
		}
	}

	public function verify($xml_file_signed,$trusted_pem_path){
		$command = "xmlsec1 --verify --id-attr:Id toto --trusted-pem $trusted_pem_path $xml_file_signed 2>&1";
		exec($command,$output,$return_var);
		return $return_var == 0;
	}

	private function getXMLSignatureTemplate(){
		return simplexml_load_file(__DIR__."/xades-template.xml");

		/*$signatureTemplate = new DOMDocument();
		$signatureTemplate->load(__DIR__."/xades-template.xml");
		return $signatureTemplate;*/
	}



//xmlsec1 --verify --id-attr:Id http://www.minefi.gouv.fr/cp/helios/pes_v2/Rev0/aller:PES_Aller --trusted-pem ~/Desktop/AC_AGENTS.cer --trusted-pem ~/Desktop/AC_Racine_G3.cer pes_aller.xml

}