<?php


class PemCertificateFactoryTest extends S2lowTestCase
{
    public function testExceptionIsThrownWhenWrongCertificateIsParsed(){
        $certificateFactory = new PemCertificateFactory();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Problème à l'ouverture du certificat : ");
        $certificateFactory->getFromString("Pas un certificat");
    }

}