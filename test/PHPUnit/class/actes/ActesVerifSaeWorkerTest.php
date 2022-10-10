<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActesVerifSaeWorker;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;
use S2lowLegacy\Model\PastellPropertiesSQL;

class ActesVerifSaeWorkerTest extends S2lowTestCase
{
    private const FAKE_PASTELL_URL = "https://fakepastellurl/";

    public function testGetAllId()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);
        $actesVerifSaeWorker = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class);

        $this->assertEquals([$transaction_id], $actesVerifSaeWorker->getAllId());
        $this->assertEquals($transaction_id, $actesVerifSaeWorker->getData($transaction_id));
        $this->assertEquals(ActesVerifSaeWorker::QUEUE_NAME, $actesVerifSaeWorker->getQueueName());
    }

    /**
     * @throws Exception
     */
    public function testCasNominal()
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapper
            ->method('get')
            ->will($this->returnCallback(function ($a) {
                if ($a == self::FAKE_PASTELL_URL . "/detail-document.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-sae-archiver.json");
                }
                if ($a == "https://fakepastellurl//recuperation-fichier.php?id_e=12&id_d=42&field=reply_sae") {
                    return file_get_contents(__DIR__ . "/fixtures/reply.xml");
                }
                if ($a == self::FAKE_PASTELL_URL . "/action.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-action-delete.json");
                }
                throw new Exception("Envoi de données inatendue : $a");
            }));

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperFactory
            ->method('getNewInstance')
            ->willReturn($curlWrapper);

        $this->getObjectInstancier()->set(CurlWrapperFactory::class, $curlWrapperFactory);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);

        $actesVerifSaeWorker = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class);

        $actesVerifSaeWorker->work($transaction_id);

        $exepected_message = "La transaction $transaction_id a été acceptée par le SAE : \n000 - Votre transfert d'archive a été accepté par la plate-forme as@lae";

        $this->assertEquals(
            $exepected_message,
            $this->getLogRecords()[3]['message']
        );

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals(
            "https://asalae.demonstrations.libriciel.fr/archives/viewByArchiveIdentifier/89",
            $transaction_info['archive_url']
        );
        $this->assertEquals(ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE, $transaction_info['last_status_id']);

        $last_status_info = $actesTransactionSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE, $last_status_info['status_id']);
        $this->assertEquals($exepected_message, $last_status_info['message']);
        $this->assertEquals(
            file_get_contents(__DIR__ . "/fixtures/reply.xml"),
            stream_get_contents($last_status_info['flux_retour'])
        );
    }

    /**
     * @throws Exception
     */
    public function testRefusPastell()
    {

        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapper
            ->method('get')
            ->will($this->returnCallback(function ($a) {
                if ($a == self::FAKE_PASTELL_URL . "/detail-document.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-sae-erreur.json");
                }
                if ($a == self::FAKE_PASTELL_URL . "/action.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-action-delete.json");
                }
                throw new Exception("Envoi de données inatendue : $a");
            }));

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperFactory
            ->method('getNewInstance')
            ->willReturn($curlWrapper);

        $this->getObjectInstancier()->set(CurlWrapperFactory::class, $curlWrapperFactory);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);

        $actesVerifSaeWorker = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class);

        $actesVerifSaeWorker->work($transaction_id);

        $exepected_message = "La transaction $transaction_id a été refusé par le SAE : (état verif-sae-erreur)";


        $this->assertEquals(
            $exepected_message,
            $this->getLogRecords()[3]['message']
        );

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE, $transaction_info['last_status_id']);

        $last_status_info = $actesTransactionSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE, $last_status_info['status_id']);
        $this->assertEquals($exepected_message, $last_status_info['message']);
    }


    /**
     * @throws Exception
     */
    public function testRefusSAE()
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapper
            ->method('get')
            ->will($this->returnCallback(function ($a) {
                if ($a == self::FAKE_PASTELL_URL . "/detail-document.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-sae-archiver.json");
                }
                if ($a == "https://fakepastellurl//recuperation-fichier.php?id_e=12&id_d=42&field=reply_sae") {
                    return file_get_contents(__DIR__ . "/fixtures/reply-refus-sae.xml");
                }
                if ($a == self::FAKE_PASTELL_URL . "/action.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-action-delete.json");
                }
                throw new Exception("Envoi de données inatendue : $a");
            }));

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperFactory
            ->method('getNewInstance')
            ->willReturn($curlWrapper);

        $this->getObjectInstancier()->set(CurlWrapperFactory::class, $curlWrapperFactory);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);

        $actesVerifSaeWorker = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class);

        $actesVerifSaeWorker->work($transaction_id);

        $exepected_message = "La transaction $transaction_id a été refusé par le SAE.\n203 - Votre transfert d'archive a été rejeté par la plate-forme as@lae";

        $this->assertEquals(
            $exepected_message,
            $this->getLogRecords()[3]['message']
        );

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE, $transaction_info['last_status_id']);

        $last_status_info = $actesTransactionSQL->getLastTransactionWorkflowInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE, $last_status_info['status_id']);
        $this->assertEquals($exepected_message, $last_status_info['message']);
        $this->assertEquals(
            file_get_contents(__DIR__ . "/fixtures/reply-refus-sae.xml"),
            stream_get_contents($last_status_info['flux_retour'])
        );
    }


    /**
     * @throws Exception
     */
    public function testEnAttente()
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapper
            ->method('get')
            ->will($this->returnCallback(function ($a) {
                if ($a == self::FAKE_PASTELL_URL . "/detail-document.php") {
                    return file_get_contents(__DIR__ . "/fixtures/pastell-response-sae-archiver.json");
                }
                if ($a == "https://fakepastellurl//recuperation-fichier.php?id_e=12&id_d=42&field=reply_sae") {
                    throw new Exception("404 not found");
                }
                throw new Exception("Envoi de données inatendue : $a");
            }));

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperFactory
            ->method('getNewInstance')
            ->willReturn($curlWrapper);

        $this->getObjectInstancier()->set(CurlWrapperFactory::class, $curlWrapperFactory);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);

        $actesVerifSaeWorker = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class);

        $actesVerifSaeWorker->work($transaction_id);


        $exepected_message = "Il n'y a pas encore de réponse (404 not found)";

        $this->assertEquals(
            $exepected_message,
            $this->getLogRecords()[3]['message']
        );

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_ENVOYE_AU_SAE, $transaction_info['last_status_id']);
    }

    /**
     * @throws Exception
     */
    public function testPastellPasBien()
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapper
            ->method('get')
            ->will($this->returnCallback(function () {
                return 'Pastell ne répond pas... ou mal';
            }));

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperFactory
            ->method('getNewInstance')
            ->willReturn($curlWrapper);

        $this->getObjectInstancier()->set(CurlWrapperFactory::class, $curlWrapperFactory);

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ENVOYE_AU_SAE);

        $actesVerifSaeWorker = $this->getObjectInstancier()->get(ActesVerifSaeWorker::class);

        $actesVerifSaeWorker->work($transaction_id);

        $exepected_message = "Problème lors de la vérification de l'archive : Impossible de décoder les données reçu : Pastell ne répond pas... ou mal";

        $this->assertEquals(
            $exepected_message,
            $this->getLogRecords()[3]['message']
        );

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_ENVOYE_AU_SAE, $transaction_info['last_status_id']);
    }

    private function createTransaction($status)
    {
        $sql = "INSERT INTO actes_envelopes(user_id) VALUES(1) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);


        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,sae_transfer_identifier) VALUES (?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql, $envelope_id, $status, 1, 1, 42);

        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = self::FAKE_PASTELL_URL;
        $pastellProperties->id_e = 12;
        $pastellProperties->actes_send_auto = true;
        $pastellProperties->actes_flux_id = 1;
        $authoritySQL->updateSAE(1, $pastellProperties);
        $pastellPropertiesSQL  = new PastellPropertiesSQL($this->getSQLQuery());
        $pastellPropertiesSQL->editProperties(1, $pastellProperties);
        return $transaction_id;
    }
}
