<?php

//http://users.dcc.uchile.cl/~pcamacho/tutorial/web/xmlsec/xmlsec.html
class XadesSignature {

	const NS_DS_URI = "http://www.w3.org/2000/09/xmldsig#";


	public function sign($xml_file_to_sign,$p12_certificate_path,$p12_password, $xml_file_signed){
		$domDocument = $this->loadDomDocument($xml_file_to_sign);

		if (! $domDocument->documentElement->attributes->getNamedItem('Id')){
			throw new Exception("Le document XML ne contient pas d'Id");
		}
		$id = $domDocument->documentElement->attributes->getNamedItem('Id')->nodeValue;


		$signatureTemplate = $this->getXMLSignatureTemplate();

		$child  = $signatureTemplate->children(self::NS_DS_URI);
		$child->SignedInfo->Reference->attributes()->URI = "#$id";

		$signature_id = "{$id}_SIG";
		$signatureTemplate->attributes(self::NS_DS_URI)->Id = $signature_id;
		//TODO NOT WORKING !
			print_r($signatureTemplate->attributes(self::NS_DS_URI));

		$xad = $child->Object->children("http://uri.etsi.org/01903/v1.1.1#");

		$signedSignatureProperties = $xad->QualifyingProperties->SignedProperties->SignedSignatureProperties;

		$signedSignatureProperties->SigningTime = gmdate('Y-m-d\TH:i:s\Z');

		$pkcs12 = new PKCS12();
		$all = $pkcs12->getAll($p12_certificate_path,$p12_password);

		$x509Certificate = new X509Certificate();
		$info = $x509Certificate->getInfo($all['cert']);

		$serialNumber = $info['serialNumber'];

		$issuerName = "";
		foreach(array_reverse($info['issuer']) as $id => $value){
			$issuerName[] = "$id=$value";
		}
		$issuerName = implode(", ",$issuerName);

		$issuer_serial_child = $signedSignatureProperties->SigningCertificate->Cert->IssuerSerial->children("http://uri.etsi.org/01903/v1.1.1#");
		$issuer_serial_child->X509IssuerName = $issuerName;
		$issuer_serial_child->X509SerialNumber = $serialNumber;

		$tmp_file = sys_get_temp_dir()."/".uniqid("x509_pem");
		file_put_contents($tmp_file,$all['cert']);

		$command = "openssl x509 -in $tmp_file -outform der | openssl sha1 -binary | openssl base64";
		exec($command,$output,$return_var);
		$certDigest = $output[0];

		$cert_digest_child = $signedSignatureProperties->SigningCertificate->Cert->CertDigest->children("http://uri.etsi.org/01903/v1.1.1#");
		$cert_digest_child->DigestValue = $certDigest;


		$signedSignatureProperties->SignatureProductionPlace->City = 'Lyon';
		$signedSignatureProperties->SignatureProductionPlace->PostalCode = '69003';
		$signedSignatureProperties->SignatureProductionPlace->CountryName = 'France';
		$signedSignatureProperties->SignerRole->ClaimedRoles->ClaimedRole = utf8_encode('Tiers de Télétransmission S2low Adullact');

		$signatureTemplateDOM = dom_import_simplexml($signatureTemplate);
		$node = $domDocument->importNode($signatureTemplateDOM, true);
		$domDocument->documentElement->appendChild($node);

		$tmp_file = sys_get_temp_dir()."/".uniqid("xmlsigtmp");
		$domDocument->save($tmp_file);

		$rootNodeName = $this->getLocalName($domDocument);
		$command = "xmlsec1 --sign --id-attr:Id $rootNodeName --output $xml_file_signed --pkcs12 $p12_certificate_path --pwd $p12_password $tmp_file 2>&1";
		exec($command,$output,$return_var);
		unlink($tmp_file);
		if ($return_var != 0){
			throw new Exception("Erreur ($return_var) lors de la signature technique : ".implode("\n",$output));
		}
	}

	public function verify($xml_file_signed,$trusted_pem_path){
		$domDocument = $this->loadDomDocument($xml_file_signed);
		$rootNodeName = $this->getLocalName($domDocument);
		$command = "xmlsec1 --verify --id-attr:Id $rootNodeName --trusted-pem $trusted_pem_path $xml_file_signed 2>&1";
		exec($command,$output,$return_var);
		return $return_var == 0;
	}

	private function loadDomDocument($xml_file_path){
		libxml_use_internal_errors(true);

		$domDocument = new DOMDocument();
		$result = $domDocument->load($xml_file_path, LIBXML_PARSEHUGE);
		if (! $result){
			$errors = libxml_get_errors();
			throw new Exception($errors[0]->message);
		}
		return $domDocument;
	}

	private function getLocalName(DomDocument $domDocument){
		return $domDocument->documentElement->localName;
	}


	private function getXMLSignatureTemplate(){
		return simplexml_load_file(__DIR__."/xades-template.xml");
	}

}