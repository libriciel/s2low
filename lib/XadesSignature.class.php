<?php

//http://users.dcc.uchile.cl/~pcamacho/tutorial/web/xmlsec/xmlsec.html
class XadesSignature {

	const NS_DS_URI = "http://www.w3.org/2000/09/xmldsig#";
	const NS_XAD_URI = "http://uri.etsi.org/01903/v1.1.1#";

	private $xmlsec1_path;
	private $pkcs12;
	private $x509Certificate;

	public function __construct($xmlsec1_path, PKCS12 $pkcs12, X509Certificate $x509Certificate){
		$this->xmlsec1_path = $xmlsec1_path;
		$this->pkcs12 = $pkcs12;
		$this->x509Certificate = $x509Certificate;
	}

	public function sign($xml_file_to_sign,$p12_certificate_path,$p12_password, $xml_file_signed, XadesSignatureProperties $xadesSignatureProperties){
		$certificate_info = $this->getCertificateInfo($p12_certificate_path,$p12_password);

		$domDocument = $this->loadDomDocument($xml_file_to_sign);
		$document_id = $this->getDocumentId($domDocument);

		if ($this->hasSignature($domDocument)){
			//Limitation de cette classe : on ne fait pas de signature multiple enveloppé...
			throw new XadesSignatureHasSignatureException("Le fichier à signer a déjà une signature");
		}

		$signatureTemplate = $this->getXMLSignatureTemplate($document_id,$certificate_info,$xadesSignatureProperties);

		$signatureTemplateDOM = dom_import_simplexml($signatureTemplate);
		$node = $domDocument->importNode($signatureTemplateDOM, true);
		$domDocument->documentElement->appendChild($node);

		$tmp_file = sys_get_temp_dir()."/".uniqid("xmlsigtmp");
		$domDocument->save($tmp_file);

		$rootNodeName = $this->getLocalName($domDocument);
		$command = "{$this->xmlsec1_path} --sign --id-attr:Id $rootNodeName --output $xml_file_signed --pkcs12 $p12_certificate_path --pwd $p12_password $tmp_file 2>&1";
		exec($command,$output,$return_var);
		unlink($tmp_file);
		if ($return_var != 0){
			throw new Exception("Erreur ($return_var) lors de la signature technique : ".implode("\n",$output));
		}
	}

	public function verify($xml_file_signed,$trusted_pem_path){
		return $this->verifyIntern($xml_file_signed,"--trusted-pem $trusted_pem_path");
	}

	public function verifyNoCA($xml_file_signed){
		return $this->verifyIntern($xml_file_signed,"");
	}

	private function verifyIntern($xml_file_signed,$sup_command){
		$rootNodeName = $this->getRootNodeName($xml_file_signed);
		$command = "{$this->xmlsec1_path} --verify --id-attr:Id $rootNodeName $sup_command $xml_file_signed 2>&1";
		echo $command;
		exec($command,$output,$return_var);
		return $return_var == 0;
	}

	private function getRootNodeName($xml_file_signed){
		$domDocument = $this->loadDomDocument($xml_file_signed);
		return $this->getLocalName($domDocument);
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

	private function getDocumentId(DOMDocument $domDocument){
		if (! $domDocument->documentElement->attributes->getNamedItem('Id')){
			throw new Exception("Le document XML ne contient pas d'Id");
		}
		return $domDocument->documentElement->attributes->getNamedItem('Id')->nodeValue;
	}

	private function hasSignature(DomDocument $domDocument){
		$nodes = $domDocument->documentElement->childNodes;
		foreach($nodes as $node){
			/** @var $node DomNode */
			if ($node->nodeType != XML_ELEMENT_NODE){
				continue;
			}
			/** @var $node DomElement */
			if (strtolower($node->localName) == 'signature' && $node->namespaceURI == self::NS_DS_URI){
				return true;
			}
		}
		return false;
	}

	private function getCertificateInfo($p12_certificate_path,$p12_password){
		$x509_pem_content = $this->pkcs12->getX509CertificateContent($p12_certificate_path,$p12_password);
		$certInfo['serialNumber'] = $this->x509Certificate->getInfo($x509_pem_content)['serialNumber'];
		$certInfo['issuerName'] = $this->x509Certificate->getIssuerDN($x509_pem_content);
		$certInfo['certDigest'] = $this->x509Certificate->getBase64Hash($x509_pem_content);
		return $certInfo;
	}

	private function getXMLSignatureTemplate($document_id,$certificate_info, XadesSignatureProperties $xadesSignatureProperties){
		$signatureTemplate =  simplexml_load_file(__DIR__."/xades-template.xml");
		$signature_id = "{$document_id}_SIG";
		$signed_properties_id = "{$signature_id}_SP";

		$signatureTemplate->attributes()->Id = $signature_id;

		$signature_element  = $signatureTemplate->children(self::NS_DS_URI);
		$signature_element->SignedInfo->Reference[0]->attributes()->URI = "#$document_id";
		$signature_element->SignedInfo->Reference[1]->attributes()->URI = "#{$signed_properties_id}";

		$xad_qualifying_properties = $signature_element->Object->children(self::NS_XAD_URI)->QualifyingProperties;
		$xad_qualifying_properties->attributes()->Target="#{$signature_id}";
		$xad_qualifying_properties->SignedProperties->attributes()->Id="$signed_properties_id";

		$signedSignatureProperties = $xad_qualifying_properties->SignedProperties->SignedSignatureProperties;

		$signedSignatureProperties->SigningTime = gmdate('Y-m-d\TH:i:s\Z');

		$issuer_serial_child = $signedSignatureProperties->SigningCertificate->Cert->IssuerSerial->children(self::NS_XAD_URI);
		$issuer_serial_child->X509IssuerName = $certificate_info['issuerName'];
		$issuer_serial_child->X509SerialNumber = $certificate_info['serialNumber'];
		$cert_digest_child = $signedSignatureProperties->SigningCertificate->Cert->CertDigest->children(self::NS_XAD_URI);
		$cert_digest_child->DigestValue = $certificate_info['certDigest'];

		$signedSignatureProperties->SignatureProductionPlace->City = utf8_encode($xadesSignatureProperties->city);
		$signedSignatureProperties->SignatureProductionPlace->PostalCode = utf8_encode($xadesSignatureProperties->postalCode);
		$signedSignatureProperties->SignatureProductionPlace->CountryName = utf8_encode($xadesSignatureProperties->countryName);
		$signedSignatureProperties->SignerRole->ClaimedRoles->ClaimedRole = utf8_encode($xadesSignatureProperties->claimedRole);

		return $signatureTemplate;
	}

}

class XadesSignatureHasSignatureException extends Exception{}