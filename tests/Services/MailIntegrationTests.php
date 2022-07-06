<?php

namespace S2low\Tests\Services;

use ObjectInstancierFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailIntegrationTests extends WebTestCase
{
    public function pem2der($pem_data)
    {
        $begin = "CERTIFICATE-----";
        $end   = "-----END";
        $pem_data = mb_substr($pem_data, mb_strpos($pem_data, $begin) + mb_strlen($begin));
        $pem_data = mb_substr($pem_data, 0, mb_strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }


    public function testBasic(){

        $objectInstancier = ObjectInstancierFactory::getObjetInstancier();

        $environment = $objectInstancier->get(\Environnement::class);

        $sqlQuery = $objectInstancier->get(\SQLQuery::class);
        $sqlQuery->exec(utf8_encode(file_get_contents(__DIR__ . "/fixtures/s2low-test-init.sql")));

        $certicat_identification_pem = file_get_contents(__DIR__ . "/../../test/api/Eric_Pommateau_RGS_2_etoiles.pem");
        $certicat_identification_der = $this->pem2der($certicat_identification_pem);
        $certicat_identification = base64_encode($certicat_identification_der);

        $serverVariables = array(
            'SSL_CLIENT_VERIFY' => 'ssl_client_verify',
            'SSL_CLIENT_S_DN' => 'subject_dn',
            'SSL_CLIENT_I_DN' => 'issuer_dn',
            'SSL_CLIENT_CERT' => $certicat_identification_pem,
            'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION' => $certicat_identification,
            'TESTING_CERTIFICATE_HASH' => 'hash_adullact_identification',
        );
        $client = static::createClient(
            array(),
            $serverVariables
        );
        $environment = $objectInstancier->get(\Environnement::class);
        foreach ($serverVariables as $key=>$serverVariable){        //Solution sale à deux problèmes :
            $environment->server()->set($key,$serverVariable);      // 1/ L'object Instancier est setté *avant* les tests ...
        }                                                           // 2/ Le client ne modifie pas la variable _SERVER

        $crawler = $client->request('GET','/index.php');
        $this->assertMatchesRegularExpression(
            "#\<title\>Tiers de téléransmission multiprotocoles\<\/title\>#",
            $crawler->html()
            );
        $this->assertResponseIsSuccessful();
        //$this->assertSelectorTextContains('h1', 'Hello World');
    }

}