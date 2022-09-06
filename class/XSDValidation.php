<?php

namespace S2lowLegacy\Class;

use DOMDocument;

class XSDValidation
{
    private $xsdPath;

    public function __construct($xsdPath)
    {
        $this->xsdPath = $xsdPath;
    }

    public function validate($xml_content)
    {
        $dom = new DomDocument();
        $dom->loadXML($xml_content);
        return $dom->schemaValidate($this->xsdPath) ;
    }
}
