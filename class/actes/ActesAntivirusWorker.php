<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;
use Exception;

class ActesAntivirusWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-antivirus';
    public const PHEANSTALK_TTR = 60  * 5;  // Le timeout du process de clamdscan est à 240, on se laisse de la marge pour
                                            // que le job reste reserved avant la fin du timeout pour éviter un
                                            // mail d'erreur critique.
    private $actesTransactionSQL;
    private $actesRetriever;
    private $actesEnvelopeSQL;

    private $antivirus;

    private $logger;

    private $workerScript;

    public function __construct(
        ActesTransactionsSQL $actesTransactionSQL,
        ActesRetriever $actesRetriever,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        Antivirus $antivirus,
        LoggerInterface $s2lowLogger,
        WorkerScript $workerScript
    ) {
        $this->actesTransactionSQL = $actesTransactionSQL;
        $this->actesRetriever = $actesRetriever;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->antivirus = $antivirus;
        $this->logger = $s2lowLogger;
        $this->workerScript = $workerScript;
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getAllId(): array
    {
        return $this->actesTransactionSQL->getTransactionForAntiVirus();
    }

    public function getData($id): mixed
    {
        return $id;
    }

    public function getMutexName($data): string
    {
        return sprintf("actes-transaction-%s", $data);
    }

    public function isDataValid($data): bool
    {
        $transaction_id = $data;
        $transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
        if ($transaction_info['antivirus_check']) {
            $this->logger->notice("La transaction $transaction_id a déjà été analysé par l'antivirus");
            return false;
        }
        return true;
    }

    /**
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function work($data)
    {
        $transaction_id = $data;
        $this->logger->info("Traitement transaction $transaction_id");

        $transaction_info = $this->actesTransactionSQL->getInfo($transaction_id);
        if ($transaction_info['antivirus_check']) {
            $this->logger->notice("La transaction $transaction_id a déjà été analysé par l'antivirus");
            return true;
        }

        $envelope_info = $this->actesEnvelopeSQL->getInfo($transaction_info["envelope_id"]);

        $archive_path = $this->actesRetriever->getPath($envelope_info['file_path']);
        if (! $this->antivirus->checkArchiveSanity($archive_path, 240)) {
            $message = $this->antivirus->getLastError();
            $this->logger->notice(
                "Un virus a été trouvé pour la transaction $transaction_id",
                [$message]
            );
            $this->actesTransactionSQL->updateStatus(
                $transaction_id,
                ActesStatusSQL::STATUS_EN_ERREUR,
                $message
            );
            return false;
        }

        $this->actesTransactionSQL->setAntivirusCheck($transaction_id);
        $this->logger->info(
            "La transaction $transaction_id ne contient pas de virus"
        );

        $this->workerScript->putJobByClassName(
            ActesAnalyseFichierAEnvoyerWorker::class,
            $transaction_info["envelope_id"]
        );
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
