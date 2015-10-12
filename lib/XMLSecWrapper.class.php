<?php

//http://users.dcc.uchile.cl/~pcamacho/tutorial/web/xmlsec/xmlsec.html
class XMLSecWrapper {

	public function sign($xml_file_to_sign,$p12_certificate_path,$p12_password, $xml_file_signed){
		libxml_use_internal_errors(true);

		$domDocument = new DOMDocument();
		$result = $domDocument->load($xml_file_to_sign, LIBXML_PARSEHUGE);
		if (! $result){
			$errors = libxml_get_errors();
			throw new Exception($errors[0]->message);
		}

		$signatureTemplate = new DOMDocument();
		$signatureTemplate->loadXML($this->getXMLSignatureTemplate());

		$node = $domDocument->importNode($signatureTemplate->documentElement, true);
		$domDocument->documentElement->appendChild($node);

		$tmp_file = sys_get_temp_dir()."/".uniqid("xmlsigtmp");
		$domDocument->save($tmp_file);

		$command = "xmlsec1 --sign --output $xml_file_signed --pkcs12 $p12_certificate_path --pwd $p12_password $tmp_file 2>&1";
		exec($command,$output,$return_var);
		unlink($tmp_file);
		if ($return_var != 0){
			throw new Exception("Erreur ($return_var) lors de la signature technique : ".implode("\n",$output));
		}
	}

	public function verify($xml_file_signed,$trusted_pem_path){
		$command = "xmlsec1 --verify --trusted-pem $trusted_pem_path $xml_file_signed 2>&1";
		exec($command,$output,$return_var);
		return $return_var == 0;
	}

	private function getXMLSignatureTemplate(){
		$xmlSignatureTemplate = <<< "SIGNATURE_TEMPLATE"
<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
	<SignedInfo>
		<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
		<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
		<Reference URI="">
			<Transforms>
				<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
			</Transforms>
			<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
			<DigestValue></DigestValue>
		</Reference>
	</SignedInfo>
	<SignatureValue />
	<KeyInfo>
		<X509Data >
			<X509SubjectName/>
			<X509IssuerSerial/>
			<X509Certificate/>
		</X509Data>
		<KeyValue />
	</KeyInfo>
</Signature>
SIGNATURE_TEMPLATE;
		return $xmlSignatureTemplate;
	}

}