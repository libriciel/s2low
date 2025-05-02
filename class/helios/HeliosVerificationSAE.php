<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Class\RecoverableException;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowLegacy\Model\PastellPropertiesSQL;

class HeliosVerificationSAE
{
    private $heliosTransactionsSQL;
    private $pastellWrapperFactory;
    private LoggerInterface $logger;
    private $authoritySQL;
    private $pastellPropertiesSQL;

    public function __construct(
        PastellWrapperFactory $pastellWrapperFactory,
        LoggerInterface $logger,
        AuthoritySQL $authoritySQL,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        PastellPropertiesSQL $pastellPropertiesSQL
    ) {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->authoritySQL = $authoritySQL;
        $this->pastellWrapperFactory = $pastellWrapperFactory;
        $this->logger = $logger;
        $this->pastellPropertiesSQL = $pastellPropertiesSQL;
    }

    public function verifArchive($transaction_id)
    {
        try {
            return $this->verifArchiveThrow($transaction_id);
        } catch (Exception $e) {
            $this->logger->error("Problème lors de la vérification de l'archive : " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int $transaction_id
     * @return bool
     * @throws RecoverableException
     * @throws UnrecoverableException
     * @throws Exception
     */
    public function verifArchiveThrow(int $transaction_id)
    {

        $transaction_info = $this->heliosTransactionsSQL->getInfo($transaction_id);

        $this->logger->info("Vérification de la transaction {$transaction_info['id']} ");

        $this->authoritySQL->verifHasPastell($transaction_info[HeliosTransactionsSQL::AUTHORITY_ID]);

        $pastellProperties = $this->pastellPropertiesSQL->getPastellProperties(
            $transaction_info[HeliosTransactionsSQL::AUTHORITY_ID]
        );

        $pastellWrapper = $this->pastellWrapperFactory->getNewInstance($pastellProperties);

        $sae_transfert_identifier = $transaction_info['sae_transfer_identifier'];

        $pastell_transaction_info = $pastellWrapper->getInfo($sae_transfert_identifier);
        if (!$pastell_transaction_info) {
            throw new RecoverableException($pastellWrapper->getLastError());
        }

        if (in_array($pastell_transaction_info['last_action']['action'], ['verif-sae-erreur','validation-sae-erreur','erreur-envoie-sae','fatal-error'])) {
            $msg = "La transaction {$transaction_info['id']} a été refusé par le SAE : (état {$pastell_transaction_info['last_action']['action']})";

            $this->heliosTransactionsSQL->updateStatus(
                $transaction_info['id'],
                HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
                $msg
            );
            $this->logger->info($msg);
            $pastellWrapper->delete($transaction_info['sae_transfer_identifier']);
            return true;
        }

        try {
            $reply_sae = $pastellWrapper->getFile($sae_transfert_identifier, 'reply_sae');
        } catch (Exception $e) {
            throw new RecoverableException("Pas encore de réponse (" . $e->getMessage() . ")");
        }

        @ $xml = simplexml_load_string($reply_sae);

        if (! $xml) {
            throw new RecoverableException("Impossible de lire le fichier reply.xml : $reply_sae");
        }


        $nodeName = strval($xml->getName());
        $xml_message = strval($xml->{'ReplyCode'}) . " - " . strval($xml->{'Comment'});

        if (
            $nodeName == 'ArchiveTransferAcceptance' ||
            ($nodeName == 'ArchiveTransferReply' && (strval($xml->{'ReplyCode'}) == '000'))
        ) {
            $url = $pastell_transaction_info['data']['url_archive'];
            $msg = "La transaction {$transaction_info['id']} a été acceptée par le SAE : \n$xml_message";
            $this->heliosTransactionsSQL->updateStatus($transaction_info['id'], 10, $msg);
            $this->heliosTransactionsSQL->setArchiveURL($transaction_info['id'], $url);
        } else {
            $msg = "La transaction {$transaction_info['id']} a été refusé par le SAE: \n$xml_message";
            $this->heliosTransactionsSQL->updateStatus($transaction_info['id'], 11, $msg);
        }

        $this->logger->info("$msg");

        $pastellWrapper->delete($sae_transfert_identifier);
        $this->logger->info("Document $sae_transfert_identifier supprimé sur Pastell");

        return true;
    }
}
