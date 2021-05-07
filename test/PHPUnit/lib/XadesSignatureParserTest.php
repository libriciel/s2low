<?php

class XadesSignatureParserTest extends S2lowTestCase{

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->XadesSignatureParser = new XadesSignatureParser();
        parent::__construct($name, $data, $dataName);
    }

    public function testExtractSigningTimeFromPesSigne(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07T11:03:01Z",
            $this->XadesSignatureParser->extractSigningTime($xml,'#BORD5397_SIG_1')
        );
    }

    public function testExtractSigningTimeFromPesSigneDeuxFois(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau_double.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07T12:48:59Z",
            $this->XadesSignatureParser->extractSigningTime($xml,'#ID1621526490_SIG_1')
        );
        $this->assertEquals(
            "2016-11-07T11:03:01Z",
            $this->XadesSignatureParser->extractSigningTime($xml,'#BORD5397_SIG_1')
        );
    }

    public function testTimestampCorrespondsToDate(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau_double.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $timestamp = $this->XadesSignatureParser->extractSigningTimeTimestamp($xml,'#ID1621526490_SIG_1');
        $date = $timestamp = $this->XadesSignatureParser->extractSigningTime($xml,'#ID1621526490_SIG_1');
        $this->assertEquals(
            $date,
            gmdate('Y-m-d\TH:i:s\Z',1478522939)
        );
    }


}