<?php

namespace S2low\Services\SimpleXmlUtils;

use S2lowLegacy\Lib\XadesSignature;

class SignatureDeleter
{
    public function deleteSignature($xml_file_signed, $xml_file_result)
    {
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $tab = $xml->children(XadesSignature::NS_DS_URI);
        if ($tab) {
            unset($tab[0]);
        }
        $xml->asXML($xml_file_result);
    }
}
