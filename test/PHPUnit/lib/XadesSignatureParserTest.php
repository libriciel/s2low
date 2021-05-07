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
            "2016-11-07 11:03:01",              //TODO : BUG POSSIBLE vérifier temps universel
            $this->XadesSignatureParser->extractSigningTime($xml,'BORD5397_SIG_1')
        );
    }

    public function testExtractSigningTimeFromPesSigne2(){
        $xml_file_signed = __DIR__."/fixtures/signe_PESALR1-26850128500020-085014-20200526113248141.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2020-05-26 15:57:36",              //TODO : BUG POSSIBLE vérifier temps universel
            $this->XadesSignatureParser->extractSigningTime($xml,'N10025_SIG_1')
        );
    }

    public function testExtractSigningTimeFromPesSigneDeuxFois(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau_double.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07 12:48:59",
            $this->XadesSignatureParser->extractSigningTime($xml,'ID1621526490_SIG_1')
        );
        $this->assertEquals(
            "2016-11-07 11:03:01",
            $this->XadesSignatureParser->extractSigningTime($xml,'BORD5397_SIG_1')
        );
    }

    public function testTimestampCorrespondsToDate(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau_double.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $timestamp = $this->XadesSignatureParser->extractSigningTimeTimestamp($xml,'ID1621526490_SIG_1');
        $date = $timestamp = $this->XadesSignatureParser->extractSigningTime($xml,'ID1621526490_SIG_1');
        $this->assertEquals(
            $date,
            gmdate('Y-m-d H:i:s',1478522939)
        );
    }


}