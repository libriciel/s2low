<?php

use PHPUnit\Framework\TestCase;

class XadesSignatureParserTest extends TestCase {

    /** @var \XadesSignatureParser  */
    private $XadesSignatureParser;

    protected function setUp() :void
    {
        parent::setUp ();
        $this->XadesSignatureParser = new XadesSignatureParser();
    }

    /**
     * @dataProvider fileProvider
     * @throws \Exception
     */
    public function testExtractRawSigningTime( string $filepath,string $target, string $expected){
        $this->assertEquals(
            $expected,
            $this->XadesSignatureParser->extractRawSigningTime(
                simplexml_load_file(__DIR__.$filepath, "SimpleXMLElement", LIBXML_PARSEHUGE),
                $target
            )
        );
    }

    public static function fileProvider(): array
    {
        return array(
            ["/fixtures/signature_bordereau.xml",'BORD5397_SIG_1',"2016-11-07T11:03:01Z"],
            ["/fixtures/signature_bordereau_double.xml","ID1621526490_SIG_1","2016-11-07T12:48:59Z"],
            ["/fixtures/signature_bordereau_double.xml","BORD5397_SIG_1","2016-11-07T11:03:01Z"]
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

    public function testWrongTarget(){
        $xml_file_signed = __DIR__."/fixtures/signature_bordereau.xml";
        $xml = simplexml_load_file($xml_file_signed, "SimpleXMLElement", LIBXML_PARSEHUGE);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("SigningTime non trouvé pour NoTarget");
        $this->XadesSignatureParser->extractRawSigningTime($xml,'NoTarget');

    }
}