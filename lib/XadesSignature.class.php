<?php

//http://users.dcc.uchile.cl/~pcamacho/tutorial/web/xmlsec/xmlsec.html
class XadesSignature {

	const NS_DS_URI = "http://www.w3.org/2000/09/xmldsig#";
	const NS_XAD_URI = "http://uri.etsi.org/01903/v1.1.1#";

	//const HASH_ALG = "sha256";
	const HASH_ALG = "sha1";

	private $xmlsec1_path;
	private $pkcs12;
	private $x509Certificate;
	private $validca_path;

	private $last_output;

	public function __construct($xmlsec1_path, PKCS12 $pkcs12, X509Certificate $x509Certificate, $validca_path) {
		$this->xmlsec1_path = $xmlsec1_path;
		$this->pkcs12 = $pkcs12;
		$this->x509Certificate = $x509Certificate;
		$this->validca_path = $validca_path;
	}

	public function getLastOutput(){
		return $this->last_output;
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

		$signature_node_id = $this->getSignatureNodeId($document_id);

		$xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature'][@Id='{$signature_node_id}']";

		$command = "{$this->xmlsec1_path} --sign --node-xpath \"$xpath\" --id-attr:Id $rootNodeName --output $xml_file_signed --pkcs12 $p12_certificate_path --pwd $p12_password $tmp_file 2>&1";

		exec($command,$output,$return_var);

		unlink($tmp_file);
		if ($return_var != 0){
			throw new Exception("Erreur ($return_var) lors de la signature technique : ".implode("\n",$output));
		}
	}

	private function getCertificateInfo($p12_certificate_path,$p12_password){
		$x509_pem_content = $this->pkcs12->getX509CertificateContent($p12_certificate_path,$p12_password);
		$certInfo['serialNumber'] = $this->x509Certificate->getInfo($x509_pem_content)['serialNumber'];
		$certInfo['issuerName'] = $this->x509Certificate->getIssuerDN($x509_pem_content,true);
		$certInfo['certDigest'] = $this->x509Certificate->getBase64Hash($x509_pem_content, self::HASH_ALG);
		return $certInfo;
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

	private function getDocumentId(DOMDocument $domDocument){
		if (! $domDocument->documentElement->attributes->getNamedItem('Id')){
			throw new XadesSignatureNoIDException("Le document XML ne contient pas d'Id");
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

	private function getXMLSignatureTemplate($document_id,$certificate_info, XadesSignatureProperties $xadesSignatureProperties){
		if (self::HASH_ALG == 'sha1'){
			$template_file = __DIR__."/xades-template-sha1.xml";
		} else {
			$template_file = __DIR__."/xades-template.xml";
		}

		$signatureTemplate =  simplexml_load_file($template_file);
		$signature_id = $this->getSignatureNodeId($document_id);
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

	private function getSignatureNodeId($document_id)
	{
		return "{$document_id}_SIG";
	}

	private function getLocalName(DomDocument $domDocument){
		return $domDocument->documentElement->localName;
	}

	public function isSigned($xml_file){
		$xml = simplexml_load_file($xml_file, "SimpleXMLElement", LIBXML_PARSEHUGE);

		$xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature']";

		$signatureNodeList = $xml->xpath($xpath);
		if ($signatureNodeList) {
			return true;
		} else {
			return false;
		}

	}

	public function verify($xml_file_signed) {
		$xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);

		$xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature']";

		$signatureNodeList = $xml->xpath($xpath);
		if (!$signatureNodeList) {
			return false;
		}

		foreach ($signatureNodeList as $signatureNode) {
			$id = $signatureNode->attributes()->Id;
			if (!$id) {

				return false;
			}
			$node_id = strval($signatureNode->children(self::NS_DS_URI)->SignedInfo->Reference->attributes()->URI);
			$node_id = ltrim($node_id, "#");
			if (!$node_id) {
				return false;
			}
			$xpath = "//*[@Id='$node_id']";
			$element = $xml->xpath($xpath);
			if (count($element) != 1) {
				return false;
			}
			$element = $element[0];
			$name = $element->getName();
			if (!$this->verifyIntern($xml_file_signed, $name, $id)) {
				return false;
			}
		}

		return true;
	}

	private function verifyIntern($xml_file_signed, $signature_node_name, $signature_node_id) {
		$xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature'][@Id='{$signature_node_id}']";
		$command = "export SSL_CERT_DIR={$this->validca_path} && {$this->xmlsec1_path} --verify --node-xpath \"$xpath\" --id-attr:Id $signature_node_name $xml_file_signed 2>&1";
		exec($command,$output,$return_var);
		$this->last_output = implode("\n",$output);
		return $return_var == 0;
	}

	public function deleteSignature($xml_file_signed,$xml_file_result){
		$xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
		$tab = $xml->children(self::NS_DS_URI);
		if ($tab){
			unset($tab[0]);
		}
		$xml->asXML($xml_file_result);
	}

}

class XadesSignatureHasSignatureException extends Exception{}

class XadesSignatureNoIDException extends Exception{}