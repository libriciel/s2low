<?php

namespace S2lowLegacy\Lib;

use DateTime;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use S2lowLegacy\Class\VerifyPemCertificate;

class XadesSignature
{
    public const NS_DS_URI = "http://www.w3.org/2000/09/xmldsig#";
    public const NS_XAD_URI = "http://uri.etsi.org/01903/v1.1.1#";

    //const HASH_ALG = "sha256";
    private const HASH_ALG = "sha1";

    private $xmlsec1_path;
    private $pkcs12;
    private $x509Certificate;
    private $validca_path;

    private $last_output;
    /** @var XadesSignatureParser  */
    private $xadesSignatureParser;
    /**
     * @var PemCertificateFactory
     */
    private $pemCertificateFactory;
    /**
     * @var VerifyPemCertificate
     */
    private $verifyPemCertificate;

    public function __construct(
        $xmlsec1_path,
        PKCS12 $pkcs12,
        X509Certificate $x509Certificate,
        $validca_path,
        XadesSignatureParser $xadesSignatureParser,
        PemCertificateFactory $pemCertificateFactory,
        VerifyPemCertificate $verifyPemCertificate
    ) {
        $this->xmlsec1_path = $xmlsec1_path;
        $this->pkcs12 = $pkcs12;
        $this->x509Certificate = $x509Certificate;
        $this->validca_path = $validca_path;
        $this->xadesSignatureParser = $xadesSignatureParser;
        $this->pemCertificateFactory = $pemCertificateFactory;
        $this->verifyPemCertificate = $verifyPemCertificate;
    }

    public function getLastOutput()
    {
        return $this->last_output;
    }

    /**
     * @throws XadesSignatureNoIDException
     * @throws XadesSignatureHasSignatureException
     * @throws Exception
     */
    public function sign($xml_file_to_sign, $p12_certificate_path, $p12_password, $xml_file_signed, XadesSignatureProperties $xadesSignatureProperties)
    {
        //throw new Exception("La signature technique n'est plus implémenté dans s2low");
        $certificate_info = $this->getCertificateInfo($p12_certificate_path, $p12_password);

        $domDocument = $this->loadDomDocument($xml_file_to_sign);
        $document_id = $this->getDocumentId($domDocument);

        if ($this->hasSignature($domDocument)) {
            //Limitation de cette classe : on ne fait pas de signature multiple enveloppé...
            throw new XadesSignatureHasSignatureException("Le fichier à signer a déjà une signature");
        }

        $signatureTemplate = $this->getXMLSignatureTemplate($document_id, $certificate_info, $xadesSignatureProperties);


        $signatureTemplateDOM = dom_import_simplexml($signatureTemplate);

        $node = $domDocument->importNode($signatureTemplateDOM, true);
        $domDocument->documentElement->appendChild($node);

        $tmp_file = sys_get_temp_dir() . "/" . uniqid("xmlsigtmp");
        $domDocument->save($tmp_file);

        $rootNodeName = $this->getLocalName($domDocument);

        $rootNodeNameEscaped = escapeshellarg($rootNodeName);
        $signature_node_id = $this->getSignatureNodeId($document_id);

        $xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature'][@Id='{$signature_node_id}']";

        $xmlSecPathEscaped = escapeshellarg($this->xmlsec1_path);
        $xPathEscaped = escapeshellarg($xpath);
        $xmlFileSignedEscaped = escapeshellarg($xml_file_signed);
        $p12CertificatePathEscaped = escapeshellarg($p12_certificate_path);
        $p12PasswordEscaped = escapeshellarg($p12_password);
        $tmpFileEscaped = escapeshellarg($tmp_file);

        $command = "{$xmlSecPathEscaped} --sign --node-xpath {$xPathEscaped} --id-attr:Id {$rootNodeNameEscaped} --output {$xmlFileSignedEscaped} --pkcs12 {$p12CertificatePathEscaped} --pwd {$p12PasswordEscaped} {$tmpFileEscaped} 2>&1";
        exec($command, $output, $return_var);

        unlink($tmp_file);
        if ($return_var != 0) {
            throw new Exception("Erreur ($return_var) lors de la signature technique : " . implode("\n", $output));
        }
    }

    private function getCertificateInfo($p12_certificate_path, $p12_password)
    {
        $x509_pem_content = $this->pkcs12->getX509CertificateContent($p12_certificate_path, $p12_password);
        $certInfo['serialNumber'] = $this->x509Certificate->getInfo($x509_pem_content)['serialNumber'];
        $certInfo['issuerName'] = $this->x509Certificate->getIssuerDN($x509_pem_content, true);
        $certInfo['certDigest'] = $this->x509Certificate->getBase64Hash($x509_pem_content, self::HASH_ALG);
        return $certInfo;
    }

    private function loadDomDocument($xml_file_path)
    {
        libxml_use_internal_errors(true);

        $domDocument = new DOMDocument();
        $result = $domDocument->load($xml_file_path, LIBXML_PARSEHUGE);
        if (! $result) {
            $errors = libxml_get_errors();
            throw new Exception($errors[0]->message);
        }
        return $domDocument;
    }

    private function getDocumentId(DOMDocument $domDocument)
    {
        if (! $domDocument->documentElement->attributes->getNamedItem('Id')) {
            throw new XadesSignatureNoIDException("Le document XML ne contient pas d'Id");
        }
        return $domDocument->documentElement->attributes->getNamedItem('Id')->nodeValue;
    }

    private function hasSignature(DomDocument $domDocument)
    {
        $nodes = $domDocument->documentElement->childNodes;
        foreach ($nodes as $node) {
            /** @var $node DomNode */
            if ($node->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            /** @var $node DomElement */
            if (mb_strtolower($node->localName) == 'signature' && $node->namespaceURI == self::NS_DS_URI) {
                return true;
            }
        }
        return false;
    }

    private function getXMLSignatureTemplate($document_id, $certificate_info, XadesSignatureProperties $xadesSignatureProperties)
    {
        if (self::HASH_ALG == 'sha1') {
            $template_file = __DIR__ . "/xades-template-sha1.xml";
        } else {
            $template_file = __DIR__ . "/xades-template.xml";
        }

        $signatureTemplate =  simplexml_load_file($template_file);
        $signature_id = $this->getSignatureNodeId($document_id);
        $signed_properties_id = "{$signature_id}_SP";

        $signatureTemplate->attributes()->Id = $signature_id;

        $signature_element  = $signatureTemplate->children(self::NS_DS_URI);
        $signature_element->SignedInfo->Reference[0]->attributes()->URI = "#$document_id";
        $signature_element->SignedInfo->Reference[1]->attributes()->URI = "#{$signed_properties_id}";

        $xad_qualifying_properties = $signature_element->Object->children(self::NS_XAD_URI)->QualifyingProperties;
        $xad_qualifying_properties->attributes()->Target = "#{$signature_id}";
        $xad_qualifying_properties->SignedProperties->attributes()->Id = "$signed_properties_id";

        $signedSignatureProperties = $xad_qualifying_properties->SignedProperties->SignedSignatureProperties;

        $signedSignatureProperties->SigningTime = gmdate('Y-m-d\TH:i:s\Z');



        $issuer_serial_child = $signedSignatureProperties->SigningCertificate->Cert->IssuerSerial->children(self::NS_DS_URI);

        $issuer_serial_child->X509IssuerName = $certificate_info['issuerName'];
        $issuer_serial_child->X509SerialNumber = $certificate_info['serialNumber'];
        $cert_digest_child = $signedSignatureProperties->SigningCertificate->Cert->CertDigest->children(self::NS_XAD_URI);
        $cert_digest_child->DigestValue = $certificate_info['certDigest'];

        $signedSignatureProperties->SignatureProductionPlace->City = $xadesSignatureProperties->city;
        $signedSignatureProperties->SignatureProductionPlace->PostalCode = $xadesSignatureProperties->postalCode;
        $signedSignatureProperties->SignatureProductionPlace->CountryName = $xadesSignatureProperties->countryName;
        $signedSignatureProperties->SignerRole->ClaimedRoles->ClaimedRole = $xadesSignatureProperties->claimedRole;

        return $signatureTemplate;
    }

    private function getSignatureNodeId($document_id)
    {
        return "{$document_id}_SIG";
    }

    private function getLocalName(DomDocument $domDocument)
    {
        return $domDocument->documentElement->localName;
    }

    public function isSigned($xml_file)
    {
        $xml = simplexml_load_file($xml_file, "SimpleXMLElement", LIBXML_PARSEHUGE);

        $xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature']";

        $signatureNodeList = $xml->xpath($xpath);
        if ($signatureNodeList) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @throws \Exception
     */
    public function verify($xml_file_signed): void
    {
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);

        $xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature']";

        $signatureNodeList = $xml->xpath($xpath);
        if (!$signatureNodeList) {
            throw new Exception("Impossible d'extraire les entités \<signatures\>");
        }

        foreach ($signatureNodeList as $signatureNode) {
            $id = $signatureNode->attributes()->Id;
            if (!$id) {
                throw new Exception("Impossible d'extraire l'attribut Id de l'entité \<Signature\>");
            }
            $signedElementRootName = $this->getSignedElementRootName($signatureNode, $xml);

            $signingTime = $this->xadesSignatureParser->extractXadesSigningTime($xml, strval($id));

            $pemCertificate = $this->pemCertificateFactory->getFromMinimalString(
                strval($signatureNode->children(self::NS_DS_URI)->KeyInfo->X509Data->X509Certificate)
            );

            $file = "/tmp/s2low_xades_" . mt_rand(0, getrandmax());
            file_put_contents($file, $pemCertificate->getContent());

            $timeStamp = null;
            if ($signingTime) {
                $timeStamp = $signingTime->getTimestamp();
            }

            try {
                $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                    $file,
                    [
                        3,  //X509_V_ERR_UNABLE_TO_GET_CRL
                        11,  //X509_V_ERR_CRL_NOT_YET_VALID
                        12  //X509_V_ERR_CRL_HAS_EXPIRED
                    ],
                    $timeStamp
                );
            } finally {
                unlink($file);
            }

            if (!$this->verifyIntern($xml_file_signed, $signedElementRootName, $id, $signingTime)) {
                throw new Exception("Impossible d'affirmer que la signature correspond au fichier");
            }
        }
    }

    private function verifyIntern(
        $xml_file_signed,
        $signature_node_name,
        $signature_node_id,
        ?DateTime $verificationTime = null,
    ): bool {
        $xpath = "//*[namespace-uri()='http://www.w3.org/2000/09/xmldsig#'][local-name()='Signature'][@Id='{$signature_node_id}']";
        $verificationTimeParameter = "";

        if ($verificationTime) {
            $verificationTimeString = $verificationTime
                ->setTimezone(new DateTimeZone('UTC'))
                ->format("Y-m-d G:i:s");
            $verificationTimeParameter = "--verification-time " . escapeshellarg($verificationTimeString);
        }

        $validCaPathEscaped = escapeshellarg($this->validca_path);
        $xmlSecPathEscaped = escapeshellarg($this->xmlsec1_path);
        $xPathEscaped = escapeshellarg($xpath);
        $signatureNodeNameEscaped = escapeshellarg($signature_node_name);
        $xmlFileSignedEscaped = escapeshellarg($xml_file_signed);

        $command = "export TZ=UTC && export SSL_CERT_DIR={$validCaPathEscaped} && {$xmlSecPathEscaped} --verify --node-xpath {$xPathEscaped} {$verificationTimeParameter} --id-attr:Id {$signatureNodeNameEscaped} {$xmlFileSignedEscaped} 2>&1";
        exec($command, $output, $return_var);
        $this->last_output = implode("\n", $output);
        return $return_var == 0;
    }

    public function deleteSignature($xml_file_signed, $xml_file_result)
    {
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $tab = $xml->children(self::NS_DS_URI);
        if ($tab) {
            unset($tab[0]);
        }
        $xml->asXML($xml_file_result);
    }

    /**
     * @param $signatureNode
     * @param $id
     * @param $xml
     * @return mixed
     * @throws \Exception
     */
    private function getSignedElementRootName($signatureNode, $xml)
    {
        $signature_node_URI =
            ltrim(
                strval($signatureNode->children(self::NS_DS_URI)->SignedInfo->Reference->attributes()->URI),
                "#"
            );

        if (empty($signature_node_URI)) {           // Si l'URI n'est pas sp�cifi�e dans le premier noeud r�f�rence,
            return $xml->getName();                 // on prend en compte l'entit� racine du XML
        }
        $xpath = "//*[@Id='$signature_node_URI']";
        $element = $xml->xpath($xpath);
        if (count($element) != 1) {
            throw new Exception("Impossible d'extraire l'entit� \<SignedProperties\> d'Id $signature_node_URI");
        }
        $element = $element[0];
        $name = $element->getName();
        return $name;
    }
}
