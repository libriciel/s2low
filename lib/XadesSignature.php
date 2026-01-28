<?php

namespace S2lowLegacy\Lib;

//http://users.dcc.uchile.cl/~pcamacho/tutorial/web/xmlsec/xmlsec.html
use S2low\DTO\XadesSignatureValidationResult;
use S2lowLegacy\Class\VerifyPemCertificate;
use DateTime;
use DateTimeZone;
use Exception;

class XadesSignature
{
    public const NS_DS_URI = "http://www.w3.org/2000/09/xmldsig#";

    private $xmlsec1_path;

    private $last_output;

    public function __construct(
        $xmlsec1_path,
        private readonly XadesSignatureParser $xadesSignatureParser,
        private readonly PemCertificateFactory $pemCertificateFactory,
        private readonly VerifyPemCertificate $verifyPemCertificate,
    ) {
        $this->xmlsec1_path = $xmlsec1_path;
    }

    public function getLastOutput()
    {
        return $this->last_output;
    }

    public function verifyWithReturn(string $xml_file_signed, string $certificateStorePath): XadesSignatureValidationResult
    {
        try {
            $this->verify($xml_file_signed, $certificateStorePath);
            return new XadesSignatureValidationResult(
                true,
                $this->getLastOutput()
            );
        } catch (Exception $exception) {
            return new XadesSignatureValidationResult(
                false,
                $this->getLastOutput(),
                $exception->getMessage()
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function verify(string $xml_file_signed, string $certificateStorePath): void
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
                    $certificateStorePath,
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

            if (!$this->verifyIntern($xml_file_signed, $certificateStorePath, $signedElementRootName, $id, $signingTime)) {
                throw new Exception("Impossible d'affirmer que la signature correspond au fichier");
            }
        }
    }

    private function verifyIntern(
        $xml_file_signed,
        string $certificateStorePath,
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
            $verificationTimeParameter = "--verification-time \"$verificationTimeString\"";
        }

        $command = "export TZ=UTC && export SSL_CERT_DIR={$certificateStorePath} && {$this->xmlsec1_path} --verify --node-xpath \"$xpath\" " . $verificationTimeParameter . " --id-attr:Id $signature_node_name $xml_file_signed 2>&1";
        exec($command, $output, $return_var);
        $this->last_output = implode("\n", $output);
        return $return_var == 0;
    }

    /**
     * @param $signatureNode
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
