<?php

class XadesSignatureParserTest extends S2lowTestCase{

    /** @var \XadesSignatureParser  */
    private $XadesSignatureParser;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->XadesSignatureParser = new XadesSignatureParser();
        parent::__construct($name, $data, $dataName);
    }

    public function testExtractRawSigningTimeFromPesSigne(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07T11:03:01Z",
            $this->XadesSignatureParser->extractRawSigningTime($xml,'BORD5397_SIG_1')
        );
    }

    public function testExtractDateSigningTimeFromPesSigne(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07T11:03:01Z",
            $this->XadesSignatureParser->extractRawSigningTime($xml,'BORD5397_SIG_1')
        );
    }

    public function testExtractLocalizedDateSigningTimeFromPesSigne(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07 12:03:01",
            $this->XadesSignatureParser->extractXadesSigningTime($xml,'BORD5397_SIG_1')
                ->setTimezone(new DateTimeZone('Europe/Paris'))
                ->format("Y-m-d G:i:s")
        );
    }

    public function testExtractSigningTimeFromPesSigneDeuxFois(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau_double.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);
        $this->assertEquals(
            "2016-11-07T12:48:59Z",
            $this->XadesSignatureParser->extractRawSigningTime($xml,'ID1621526490_SIG_1')
        );
        $this->assertEquals(
            "2016-11-07T11:03:01Z",
            $this->XadesSignatureParser->extractRawSigningTime($xml,'BORD5397_SIG_1')
        );
    }

    public function testTimestampCorrespondsToDate(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau_double.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);

        $this->assertEquals(
            $this->XadesSignatureParser
                ->extractXadesSigningTime($xml,'ID1621526490_SIG_1')
                ->format('Y-m-d H:i:s'),
            gmdate(
                'Y-m-d H:i:s',
                $this->XadesSignatureParser
                    ->extractXadesSigningTime($xml,'ID1621526490_SIG_1')
                    ->getTimestamp()
            )
        );
    }
}