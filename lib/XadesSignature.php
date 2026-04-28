<?php

namespace S2lowLegacy\Lib;

use DateTime;
use DateTimeZone;
use Exception;
use S2lowLegacy\Class\VerifyPemCertificate;

class XadesSignature
{
    public const NS_DS_URI = "http://www.w3.org/2000/09/xmldsig#";

    private $xmlsec1_path;
    private $validca_path;

    private $last_output;

    public function __construct(
        $xmlsec1_path,
        $validca_path,
        private readonly XadesSignatureParser $xadesSignatureParser,
        private readonly PemCertificateFactory $pemCertificateFactory,
        private readonly VerifyPemCertificate $verifyPemCertificate,
    ) {
        $this->xmlsec1_path = $xmlsec1_path;
        $this->validca_path = $validca_path;
    }

    public function getLastOutput()
    {
        return $this->last_output;
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
                        12,  //X509_V_ERR_CRL_HAS_EXPIRED
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
