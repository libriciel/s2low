<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\S2lowLogger;
use Exception;

class ActesEnvoiFichierWorker implements IWorker
{
    public const QUEUE_NAME = "actes-envoi-fichier";
    public const PHEANSTALK_TTR = 60 * 5;
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actesScriptHelper;
    private $actesTransmissionWindowsSQL;
    private $actesFileSender;
    private $actes_ministere_acronyme;

    public function __construct(
        S2lowLogger $logger,
        ActesTransactionsSQL $actesTransactionsSQL,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        ActesScriptHelper $actesScriptHelper,
        ActesTransmissionWindowsSQL $actesTransmissionWindowsSQL,
        ActesFileSender $actesFileSender,
        $actes_ministere_acronyme
    ) {
        $this->logger = $logger;
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesScriptHelper = $actesScriptHelper;
        $this->actesTransmissionWindowsSQL = $actesTransmissionWindowsSQL;
        $this->actesFileSender = $actesFileSender;
        $this->actes_ministere_acronyme = $actes_ministere_acronyme;
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
        return $this->actesTransactionsSQL->getEnveloppeIdByTransactionsStatus(
            ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION
        );
    }

    /**
     * @param $enveloppe_id
     * @return bool
     * @throws RecoverableException
     */
    public function work($enveloppe_id)
    {

        $transaction_ids = $this->actesTransactionsSQL->getIdByEnvelopeId($enveloppe_id);

        //On vérifie qu'on est dans l'état qui va bien car si on fait un rebuild-queue pendant le traitement d'une transaction,
        //celle-ci peut être envoyé deux fois.
        foreach ($transaction_ids as $transaction_id) {
            $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);
            if ($transaction_info['last_status_id'] != ActesStatusSQL::STATUS_EN_ATTENTE_DE_TRANSMISSION) {
                $this->logger->error(
                    "La transaction $transaction_id à poster n'est pas en attente de transmission : état {$transaction_info['last_status_id']} trouvé"
                );
                return false;
            }
        }

        $envelope_libelle = "enveloppe $enveloppe_id (transactions " . implode(",", $transaction_ids) . ")";

        $this->logger->debug("[$envelope_libelle] Envoi");
        $envelope_info = $this->actesEnvelopeSQL->getInfo($enveloppe_id);

        if (! $this->actesTransmissionWindowsSQL->canSend($envelope_info['file_size'])) {
            $this->logger->notice("[$envelope_libelle] Impossible d'envoyer la transaction : la fenêtre est pleine");
            return false;
        }
        try {
            $archive_path =  $this->actesScriptHelper->getArchivePath($enveloppe_id);
            $this->actesFileSender->send($archive_path);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message = "[$envelope_libelle] Impossible d'envoyer l'archive : $message";
            $this->logger->error($message);
            throw new RecoverableException($message, $e->getCode(), $e);
        }
        $this->logger->info("[$envelope_libelle] L'archive a été envoyé");

        $this->actesScriptHelper->updateStatusAndLog(
            $transaction_ids,
            ActesStatusSQL::STATUS_TRANSMIS,
            "Transmis au {$this->actes_ministere_acronyme}"
        );

        $this->actesTransmissionWindowsSQL->addFile($envelope_info['file_size']);

        return true;
    }

    public function isDataValid($data): bool
    {
        return true;
    }

    public function start(): void
    {
        // TODO: Implement start() method.
    }

    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
