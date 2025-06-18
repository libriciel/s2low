<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\SimpleXMLWrapper;
use Exception;
use S2lowLegacy\Model\PastellPropertiesSQL;
use SimpleXMLElement;

class ActesVerifSaeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-verif-sae';

    private $actesTransactionsSQL;
    private $logger;
    private $pastellPropetiesSQL;
    private $pastellWrapperFactory;


    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        S2lowLogger $s2lowLogger,
        PastellPropertiesSQL $pastellPropetiesSQL,
        PastellWrapperFactory $pastellWrapperFactory
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->logger = $s2lowLogger;
        $this->pastellPropetiesSQL = $pastellPropetiesSQL;
        $this->pastellWrapperFactory = $pastellWrapperFactory;
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getData($id): mixed
    {
        return $id;
    }

    public function getAllId(): array
    {
        return $this->actesTransactionsSQL->getTransactionToSendSAE(
            ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            true
        );
    }

    /**
     * @param $transaction_id
     * @return void
     */
    public function work($transaction_id)
    {
        try {
            $this->verifArchiveThrow($transaction_id);
        } catch (Exception $e) {
            $this->logger->error("Problème lors de la vérification de l'archive : " . $e->getMessage());
        }
    }

    /**
     * @param $transaction_id
     * @return bool
     * @throws Exception
     */
    public function verifArchiveThrow($transaction_id): bool
    {

        $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);

        $this->logger->info("Vérification de la transaction {$transaction_info['unique_id']} ({$transaction_info['id']}) sur Pastell");

        $pastellProperties = $this->pastellPropetiesSQL->getPastellProperties($transaction_info['authority_id']);
        $pastellWrapper = $this->pastellWrapperFactory->getNewInstance($pastellProperties);


        $pastell_transaction_info = $pastellWrapper->getInfo($transaction_info['sae_transfer_identifier']);

        if (in_array($pastell_transaction_info['last_action']['action'], ['verif-sae-erreur','validation-sae-erreur','erreur-envoie-sae','fatal-error'])) {
            $msg = "La transaction {$transaction_info['id']} a été refusé par le SAE : (état {$pastell_transaction_info['last_action']['action']})";

            $this->actesTransactionsSQL->updateStatus(
                $transaction_info['id'],
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
                $msg
            );
            $this->logger->info($msg);
            $pastellWrapper->delete($transaction_info['sae_transfer_identifier']);
            return true;
        }

        try {
            $reply_sae = $pastellWrapper->getFile($transaction_info['sae_transfer_identifier'], 'reply_sae');
        } catch (Exception $e) {
            $this->logger->error("Il n'y a pas encore de réponse (" . $e->getMessage() . ")");
            return false;
        }

        $simpleXMLWrapper = new SimpleXMLWrapper();
        $xml = $simpleXMLWrapper->loadString($reply_sae);

        if (! $this->isTransfertAccepted($xml)) {
            $msg = "La transaction {$transaction_info['id']} a été refusé par le SAE.\n" . $this->getXMLMessage($xml);
            $this->actesTransactionsSQL->updateStatus(
                $transaction_info['id'],
                ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
                $msg,
                $xml->asXML()
            );
            $this->logger->info($msg);
            $pastellWrapper->delete($transaction_info['sae_transfer_identifier']);
            return true;
        }

        $msg = "La transaction {$transaction_info['id']} a été acceptée par le SAE : \n" . $this->getXMLMessage($xml);
        $this->actesTransactionsSQL->updateStatus(
            $transaction_info['id'],
            ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE,
            $msg,
            $reply_sae
        );
        $this->logger->info($msg);

        $this->actesTransactionsSQL->setArchiveURL($transaction_info['id'], $pastell_transaction_info['data']['url_archive']);
        $pastellWrapper->delete($transaction_info['sae_transfer_identifier']);
        return true;
    }

    private function isTransfertAccepted(SimpleXMLElement $xml): bool
    {
        $nodeName = strval($xml->getName());
        return ($nodeName == 'ArchiveTransferAcceptance' || ($nodeName == 'ArchiveTransferReply' && (strval($xml->{'ReplyCode'}) == '000')));
    }

    private function getXMLMessage(SimpleXMLElement $xml): string
    {
        return strval($xml->{'ReplyCode'}) . " - " . strval($xml->{'Comment'});
    }

    public function getMutexName($data): string
    {
        return sprintf("actes-transaction-%s", $data);
    }

    public function isDataValid($data): bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function start(): void
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
