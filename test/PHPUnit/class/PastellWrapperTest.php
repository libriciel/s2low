<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Class\PastellWrapper;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Model\PastellProperties;

class PastellWrapperTest extends TestCase
{
    private function getS2lowLogger()
    {
        $testHandler = new TestHandler();
        $logger = new Logger('phpunit');
        $logger->pushHandler($testHandler);
        return new S2lowLogger($logger);
    }

    /**
     * @throws Exception
     */
    public function testTestConnexion()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '[{"id_e":"34","denomination":"FORMATION ERIC","siren":"000000000","type":"collectivite","centre_de_gestion":"0","entite_mere":"0"}]'
        );

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 34;



        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertTrue($pastellWrapper->testConnexion());
    }

    /**
     * @throws Exception
     */
    public function testTestConnexionReturnNotInJSON()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory("not_in_json");

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 34;

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de décoder les données reçu : not_in_json");
        $pastellWrapper->testConnexion();
    }

    /**
     * @throws Exception
     */
    public function testTestConnexionReturnPastellError()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '{"status": "error","error-message": "Acces interdit id_e=1, droit=entite:lecture,id_u=15"}'
        );

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 34;

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Message de Pastell : Acces interdit id_e=1, droit=entite:lecture,id_u=15");
        $pastellWrapper->testConnexion();
    }

    /**
     * @throws Exception
     */
    public function testTestConnexionReturnNoData()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            ''
        );

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 34;

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("curl_mock_last_error");
        $pastellWrapper->testConnexion();
    }

    /**
     * @throws Exception
     */
    public function testTestConnexionReturnBadIdEntite()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '[{"id_e":"34","denomination":"FORMATION ERIC","siren":"000000000","type":"collectivite","centre_de_gestion":"0","entite_mere":"0"}]'
        );

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertFalse($pastellWrapper->testConnexion());
        $this->assertEquals(
            "L'entité Pastell « 35 » n'est pas autorisé pour l'utilisateur « toto ».",
            $pastellWrapper->getLastError()
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateActes()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            ''
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertEquals(
            42,
            $pastellWrapper->createActes([
                'nature_code' => 4,
                'number' => 12,
                'subject' => 'test',
                'decision_date' => '2018-10-22',
                'classification' => '3.1'
            ])
        );
    }

    public function testCreateActesWithAccents()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            ''
        );
        /** @var \PHPUnit\Framework\MockObject\MockObject | CurlWrapper $curlWrapper */
        $curlWrapper =  $curlWrapperFactory->getNewInstance();

        $curlWrapper->method("addPostData")->withConsecutive(
            ["id_e","35"],
            ["id_d","42"],
            ["acte_nature","4"],
            ["numero_de_lacte","12"],
            ["objet",utf8_decode("testéöà")],
            ["date_de_lacte","2018-10-22"],
            ["classification","3.1"]
        );

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertEquals(
            42,
            $pastellWrapper->createActes([
                'nature_code' => 4,
                'number' => 12,
                'subject' => 'testéöà',
                'decision_date' => '2018-10-22',
                'classification' => '3.1'
            ])
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateActesFailed()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '',
            '{"id_e":12}'
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de créer le document sur Pastell");

        $pastellWrapper->createActes([
            'nature_code' => 4,
            'number' => 12,
            'subject' => 'test',
            'decision_date' => '2018-10-22',
            'classification' => '3.1'
        ])
        ;
    }

    /**
     * @throws Exception
     */
    public function testCreateHelios()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            ''
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertEquals(
            42,
            $pastellWrapper->createHelios([
                'filename' => 'test',
                'id' => 12,
            ])
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateHeliosFailed()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '',
            '{"id_e":12}'
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible de créer le document sur Pastell");
        $pastellWrapper->createHelios([
            'filename' => 'test',
            'id' => 12,
        ]);
    }

    /**
     * @throws Exception
     */
    public function testWrapping()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '',
            '{"id_e":12}'
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertNotEmpty($pastellWrapper->setDatePostage(42, '2018-10-22'));
        $this->assertNotEmpty($pastellWrapper->postSignature(42, __DIR__));
        $this->assertNotEmpty($pastellWrapper->postActes(42, __DIR__, "aaa.xml"));
        $this->assertNotEmpty($pastellWrapper->postAnnexe(42, __DIR__, "aaa.xml"));
        $this->assertNotEmpty($pastellWrapper->postARActes(42, __DIR__));

        $this->assertNotEmpty($pastellWrapper->postRelatedTransaction(
            42,
            [1 => 'courier simple'],
            [[1,2]],
            [[1,2]]
        ));               // Par symétrie entre les rôles de $echange_prefecture
                                                              // et $echange_prefecture_ar

        $this->assertNotEmpty($pastellWrapper->sendSAE(42));
        $this->assertNotEmpty($pastellWrapper->getInfo(42));
        $this->assertNotEmpty($pastellWrapper->delete(42));
    }


    /**
     * @throws Exception
     */
    public function testGetFile()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '',
            '{"id_e":12}'
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->assertEquals('data', $pastellWrapper->getFile(42, 'aaa'));
    }

    /**
     * @throws Exception
     */
    public function testGetFileFailed()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '',
            '{"id_e":12}',
            ''
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("curl_mock_last_error");
        $pastellWrapper->getFile(42, 'aa');
    }

    private function getCurlWrapperFactory($return_list_entite, $return_create_document = '{"id_d":42}', $recuperation_fichier = "data")
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)->getMock();

        $curlWrapper
            ->method("get")
            ->will(
                $this->returnCallback(function ($in) use ($return_list_entite, $return_create_document, $recuperation_fichier) {
                    if ($in == 'url/list-entite.php') {
                        return $return_list_entite;
                    }
                    if (preg_match("#create-document.php#", $in)) {
                        return $return_create_document;
                    }
                    if (preg_match("#modif-document.php#", $in)) {
                        return '{"id_d":42}';
                    }
                    if (preg_match("#action.php#", $in)) {
                        return '{"id_d":42}';
                    }
                    if (preg_match("#detail-document.php#", $in)) {
                        return '{"id_d":42}';
                    }
                    if (preg_match("#recuperation-fichier.php#", $in)) {
                        return $recuperation_fichier;
                    }

                    return $in;
                })
            );

        $curlWrapper
            ->method('getLastError')
            ->willReturn("curl_mock_last_error");

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)->getMock();

        $curlWrapperFactory
            ->method('getNewInstance')
            ->willReturn($curlWrapper);
        /**
         * @var CurlWrapperFactory $curlWrapperFactory
         */
        return $curlWrapperFactory;
    }

    /**
     * @throws Exception
     */
    public function testTestConnexionWithoutURL()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            '[{"id_e":"34","denomination":"FORMATION ERIC","siren":"000000000","type":"collectivite","centre_de_gestion":"0","entite_mere":"0"}]'
        );

        $pastellProperties = new PastellProperties();

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("URL Pastell non configurée");
        $pastellWrapper->testConnexion();
    }

    public function testgetModifDocumentInfoWithoutClassificationString()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            ''
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());


        $this->assertEquals(
            $pastellWrapper->getModifDocumentInfo(
                "1",
                ['nature_code' => 4,
                'number' => 12,
                'subject' => 'test',
                'decision_date' => '2018-10-22',
                'classification' => '3.1']
            ),
            [
                'id_e' => 35,
                'id_d' => '1',
                'acte_nature' => 4,
                'numero_de_lacte' => 12,
                'objet' => 'test',
                'date_de_lacte' => '2018-10-22',
                'classification' => '3.1',
                'envoi_sae' => 1,
                'has_bordereau' => 1
            ]
        );
    }

    public function testgetModifDocumentInfoWithClassificationString()
    {
        $curlWrapperFactory = $this->getCurlWrapperFactory(
            ''
        );
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "url";
        $pastellProperties->id_e = 35;
        $pastellProperties->login = "toto";

        $pastellWrapper = new PastellWrapper($pastellProperties, $curlWrapperFactory, $this->getS2lowLogger());


        $this->assertEquals(
            $pastellWrapper->getModifDocumentInfo(
                "1",
                ['nature_code' => 4,
                    'number' => 12,
                    'subject' => 'test',
                    'decision_date' => '2018-10-22',
                    'classification' => '3.1',
                    'classification_string' => 'test'
                ]
            ),
            [
                'id_e' => 35,
                'id_d' => '1',
                'acte_nature' => 4,
                'numero_de_lacte' => 12,
                'objet' => 'test',
                'date_de_lacte' => '2018-10-22',
                'classification' => '3.1 - test',
                'envoi_sae' => 1,
                'has_bordereau' => 1
            ]
        );
    }
}
