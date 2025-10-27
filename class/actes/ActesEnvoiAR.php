<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\TmpFolder;
use Exception;
use S2lowLegacy\Lib\SigTermHandler;
use Libriciel\LibActes\FichierXML\MessageMetierDemandePieceComplementaire;
use Libriciel\LibActes\FichierXML\MessageMetierARDemandePieceComplementaire;
use Libriciel\LibActes\FichierXML\MessageMetierLettreObservations;
use Libriciel\LibActes\FichierXML\MessageMetierARLettreObservations;
use Libriciel\LibActes\ArchiveData;
use Libriciel\LibActes\Archive;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActesEnvoiAR
{
    private $actesTransactionsSQL;
    private $logger;
    private $actesEnvelopeSQL;
    private $actesEnvelopeSerialSQL;
    private $actesFileSender;
    private $actesScriptHelper;

    public function __construct(
        #[Autowire(service: 'app.localFileResolver.acte_enveloppe')]
        private readonly LocalFileResolver $acteEnveloppeFileResolver,
        #[Autowire(service: 'app.store.file.acte_enveloppe')]
        private readonly CloudFileStorageInterface $cloudFileStorage,
        ActesTransactionsSQL $actesTransactionsSQL,
        LoggerInterface $logger,
        ActesEnvelopeSQL $actesEnvelopeSQL,
        ActesEnvelopeSerialSQL $actesEnvelopeSerialSQL,
        ActesFileSender $actesFileSender,
        ActesScriptHelper $actesScriptHelper,
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->logger = $logger;
        $this->actesEnvelopeSQL = $actesEnvelopeSQL;
        $this->actesEnvelopeSerialSQL = $actesEnvelopeSerialSQL;
        $this->actesFileSender = $actesFileSender;
        $this->actesScriptHelper = $actesScriptHelper;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function sendAllAR()
    {
        $sigtermHandler = SigTermHandler::getInstance();
        $this->logger->info("Lancement du script");
        $transaction_ids = $this->actesTransactionsSQL->getArchiveFStatus(ActesStatusSQL::STATUS_DOCUMENT_RECU);
        $this->logger->info("Envoie de " . count($transaction_ids) . " enveloppes de transaction à l'état DOCUMENT RECU");
        foreach ($transaction_ids as $transaction_id) {
            $this->envoiAR($transaction_id['id']);
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
        }
        $this->logger->info("Fin du script");
        return true;
    }

    /**
     * @param $transaction_id
     * @throws Exception
     */
    private function envoiAR($transaction_id)
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        try {
            $this->envoiARThrow($transaction_id, $tmp_folder);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'envoi de l'AR de la transaction $transaction_id : " . $e->getMessage());
        }
        $tmpFolder->delete($tmp_folder);
    }

    /**
     * @param $transaction_id
     * @param $tmp_folder
     * @throws Exception
     */
    private function envoiARThrow($transaction_id, $tmp_folder)
    {
        $this->logger->info("Envoi de l'AR pour la transaction $transaction_id");
        $transaction_info = $this->actesTransactionsSQL->getInfo($transaction_id);

        $envelope_info = $this->actesEnvelopeSQL->getInfo($transaction_info['envelope_id']);

        $archive = new Archive();

        $archive_path = $this->acteEnveloppeFileResolver->getFullPath($envelope_info['id']);
        $this->cloudFileStorage->downloadFileFromCloud($envelope_info['id']);

        $archiveData = $archive->getArchiveDataFromTarball(
            $archive_path,
            $tmp_folder
        );
        if ($transaction_info['type'] == 3) {
            /** @var MessageMetierDemandePieceComplementaire $messageMetierAller */
            $messageMetierAller = $archiveData->fichierXML[0];
            $messageMetierAR = new MessageMetierARDemandePieceComplementaire();
            $messageMetierAR->date_courrier_pref = $messageMetierAller->date_courrier_pref;
        } elseif ($transaction_info['type'] == 4) {
            /** @var MessageMetierLettreObservations $messageMetierDemandePieceComplementaire */
            $messageMetierAller = $archiveData->fichierXML[0];
            $messageMetierAR = new MessageMetierARLettreObservations();
            $messageMetierAR->date_courrier_pref = $messageMetierAller->date_lettre_observation;
        } else {
            throw new Exception("Message de type {$transaction_info['type']} non géré !");
        }

        $messageMetierAR->id_actes = $transaction_info['unique_id'];
        $messageMetierAR->departement = $envelope_info['department'];
        $messageMetierAR->siren = $envelope_info['siren'];
        $messageMetierAR->date_actes = date("Y-m-d", strtotime($transaction_info['decision_date']));
        $messageMetierAR->numero_interne = $transaction_info['number'];
        $messageMetierAR->code_nature_numerique = $transaction_info['nature_code'];

        $archiveDataReponse = new ArchiveData();
        $archiveDataReponse->date_generation = date("Ymd");
        $archiveDataReponse->numero_sequentiel = $this->actesEnvelopeSerialSQL->getNext($transaction_info['authority_id']);
        $archiveDataReponse->id_tdt = ACTES_APPLI_TRIGRAMME;
        $archiveDataReponse->id_application = ACTES_APPLI_QUADRIGRAMME;
        $archiveDataReponse->siren = $envelope_info['siren'];
        $archiveDataReponse->departement = $envelope_info['department'];
        $archiveDataReponse->arrondissement = $envelope_info['district'];
        $archiveDataReponse->nature = $envelope_info['authority_type_code'];
        $archiveDataReponse->telephone_referent = $envelope_info['telephone'];
        $archiveDataReponse->email_referent = $envelope_info['email'];
        $archiveDataReponse->nom_referent = $envelope_info['name'];

        $archiveDataReponse->adresses_retour = array(ACTES_TDT_MAIL_ADDRESS);

        $archiveDataReponse->fichierXML = array($messageMetierAR);
        $archive_path = $archive->generateZip($archiveDataReponse, $tmp_folder);

        $this->actesFileSender->send($archive_path);

        $this->actesScriptHelper->updateStatusAndLog(
            array($transaction_id),
            ActesStatusSQL::STATUS_ACQUITTEMENT_ENVOYE,
            "Acquittement envoyé"
        );
    }
}
