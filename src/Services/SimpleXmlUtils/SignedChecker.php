<?php

namespace S2low\Services\SimpleXmlUtils;

class SignedChecker
{
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
}
