<?php

namespace S2low\Services\Helios;

use Exception;
use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager;
use S2low\Services\LocalFileResolver;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2low\Services\SimpleXmlUtils\SignedChecker;
use S2lowLegacy\Class\Antivirus;
use S2lowLegacy\Class\helios\FichierCompteur;
use S2lowLegacy\Class\helios\HeliosTransmissionWindowsSQL;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\HeliosNamesGenerator;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\PesAllerReader;
use S2lowLegacy\Lib\XadesSignature;
use S2lowLegacy\Lib\XadesSignatureParser;
use S2lowLegacy\Model\AuthoritySiretSQL;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SimpleXMLElement;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ZipArchive;

class HeliosEnvoiControler
{
    private bool $do_not_verify_nom_fic_unicity = false;
    private XadesSignature $xadesSignature;

    public function __construct(
        private readonly AuthoritySiretSQL $authoritySiretSQL,
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly AuthoritySQL $authoritySQL,
        private readonly HeliosTransmissionWindowsSQL $heliosTransmissionWindowsSQL,
        #[Autowire(service: 'app.localFileResolver.pes_aller')]
        private readonly LocalFileResolver $pesAllerResolver,
        #[Autowire(service: 'app.store.file.pes_aller')]
        private readonly CloudFileStorageInterface $cloudPesAllerStorage,
        private readonly Antivirus $antivirus,
        private readonly WorkerScript $workerScript,
        private readonly MailerSymfonyFactory $mailerFactory,
        private readonly DGFiPConnectionsManager $heliosConnectionsConfigurationManager,
        private readonly FichierCompteur $fichierCompteur,
        private readonly LoggerInterface $logger,
        private readonly PesAllerReader $pesAllerReader,
        private readonly HeliosNamesGenerator $namesGenerator,
        private readonly SignedChecker $signedChecker,
    ) {
        $this->xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            EXTENDED_VALIDCA_PATH,
            new XadesSignatureParser(),
            new PemCertificateFactory(),
            new VerifyPemCertificate(new OpenSSLWrapper(new CommandLauncher()))
        );
    }

    public function setDoNotVerifyNomFicUnicity(bool $do_not_verify_nom_fic_unicity): void
    {
        $this->do_not_verify_nom_fic_unicity = $do_not_verify_nom_fic_unicity;
    }


    /**
     * @throws \Exception
     */
    public function validateOneTransaction($transaction_id): void
    {
        libxml_use_internal_errors(true);
        $transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

        $file_path = $this->pesAllerResolver->getFullPath($transaction_id);
        $this->cloudPesAllerStorage->downloadFileFromCloud($transaction_id);

        if (!file_exists($file_path)) {
            $message = "Transaction $transaction_id : fichier non trouvé en local ou sur le cloud";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        $pes_content = file_get_contents($file_path);
        if (! $pes_content) {
            $message = "Transaction $transaction_id : le fichier est introuvable";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        if (!$this->antivirus->checkArchiveSanity($file_path)) {
            $message = "Transaction $transaction_id : un virus a été detecté dans le fichier PES";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        //Pas de validation des fichier PES_Aller - Il ne s'agit pas d'une exigence.
        /*$heliosPESValidation = new HeliosPESValidation(HELIOS_XSD_PATH);
        if (! $heliosPESValidation->validate($pes_content)){
            print_r($heliosPESValidation->getLastError());
            $message = "Transaction $transaction_id : la transaction ne respecte pas le schéma PES_Aller";
            $this->updateStatus($transaction_id,HeliosTransactionsSQL::ERREUR,$message,$transactionInfo['user_id']);
            continue;
        }*/

        if (! $this->isInIso8859($pes_content)) {
            $message = "Transaction $transaction_id : ce fichier n'est pas encodé en ISO-8859-1";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        $pes_xml = simplexml_load_string($pes_content, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if (!$pes_xml) {
            $message = "Transaction $transaction_id : ce fichier n'est pas en XML";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        if ($this->isPESEmpty($pes_xml)) {
            $message = "Transaction $transaction_id : ce fichier ne contient ni bordereau, ni PJ, ni marché";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        $info_from_pes_aller = $this->extratInfoFromPESAller($pes_xml);

        $nom_fic = $info_from_pes_aller['nom_fic'];
        if (! $nom_fic) {
            $message = "Transaction $transaction_id : La balise Enveloppe/Parametre/NomFic n'est pas présente ou est vide";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        if (strlen($info_from_pes_aller['cod_col']) > 3) {
            $message = "Transaction $transaction_id : Le CodCol est trop long";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        if (sha1_file($file_path) != $transactionInfo['sha1']) {
            $this->updateStatus(
                $transaction_id,
                HeliosTransactionsSQL::ERREUR,
                'Le fichier a été modifé depuis son postage sur la plateforme',
                $transactionInfo['user_id']
            );
            return;
        }

        if ($this->signedChecker->isSigned($file_path)) {
            try {
                $this->xadesSignature->verify($file_path);
            } catch (Exception $exception) {
                $this->updateStatus(
                    $transaction_id,
                    HeliosTransactionsSQL::ERREUR,
                    'La signature du fichier est invalide : ' . $exception->getMessage(),
                    $transactionInfo['user_id']
                );
                return;
            }
        }
        $authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

        if (! $this->verifNomFicUnicity($authorityInfo, $info_from_pes_aller)) {
            $message = "Transaction $transaction_id : ce fichier existe déjà sur la plateforme";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }
        try {
            $this->heliosTransactionsSQL->setInfoFromPESAller($transaction_id, $info_from_pes_aller);
        } catch (Exception $e) {
            $message = "Transaction $transaction_id : problème d'enregistrement du PES Aller en base de données.";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        $siret = $pes_xml->EnTetePES->IdColl['V'];

        if (strlen($siret) > 14) {
            $message = "Transaction $transaction_id : La balise IdCol est trop longue";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        $this->authoritySiretSQL->add($transactionInfo['authority_id'], $siret);

        $usePasstransMsg = $authorityInfo['helios_use_passtrans'] ? ' [Passtrans]' : '';
        $message = "Transaction $transaction_id dans la file d'attente" . $usePasstransMsg;
        $this->updateStatus($transaction_id, HeliosTransactionsSQL::ATTENTE, $message, $transactionInfo['user_id']);

        //TODO : Quickfix pour permettre d'utiliser un Worker utilisant des composants Symfony

        $this->workerScript->putJobByQueueName(
            HeliosEnvoiWorker::getQueueNameParametre($authorityInfo['helios_use_passtrans']),
            $transaction_id,
            HeliosEnvoiWorker::PHEANSTALK_TTR
        );
        libxml_use_internal_errors(false);
    }

    private function isInIso8859($pes_content): bool
    {
        $first_line = strtoupper(mb_substr($pes_content, 0, 50));
        return str_contains($first_line, 'ISO-8859-1');
    }

    private function verifNomFicUnicity($authorityInfo, $info_from_pes_aller): bool
    {
        if ($this->do_not_verify_nom_fic_unicity) { //ARE YOU SURE ?
            if ($authorityInfo['helios_do_not_verify_nom_fic_unicity']) { //VERY SURE ?
                //OK, SO LET'S GO...
                return true;
            }
        }
        return ! $this->heliosTransactionsSQL->nomFicExists(
            $info_from_pes_aller['nom_fic'],
            $info_from_pes_aller['cod_col']
        );
    }

    private function updateStatus($transaction_id, $status_id, $message, $user_id): void
    {
        $this->logger->info($message);
        $this->heliosTransactionsSQL->updateStatus($transaction_id, $status_id, $message);
        Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios', false, $user_id);
    }

    /**
     * @throws Exception
     */
    public function sendOneTransaction($transaction_id, bool $usePasstrans): void
    {
        $file_sending_repository = HELIOS_FILES_UPLOAD_TMP;

        $this->logger->info("Préparation de l'envoi de la transaction $transaction_id\n");
        $transactionInfo = $this->heliosTransactionsSQL->getInfo($transaction_id);

        if (! $this->heliosTransmissionWindowsSQL->canSend($transactionInfo['file_size'])) {
            $this->logger->info("La fenêtre d'envoie est pleine \n");
            if (! $transactionInfo['warning_sent'] && $this->heliosTransactionsSQL->mustSendWarning($transaction_id)) {
                $message = "La transaction Helios $transaction_id est en attente depuis plus de 48H !";
                Log::newEntry(LOG_ISSUER_NAME, $message, 1, false, 'USER', 'helios', false, $transactionInfo['user_id']);
                $this->logger->info($message);
                $mail = $this->mailerFactory->getInstance();
                $mail->addRecipient(EMAIL_ADMIN);
                $mail->sendMail('Transaction Helios bloquée', $message);
                $this->heliosTransactionsSQL->setSendWarning($transaction_id);
            }
            return;
        }

        $authorityInfo = $this->authoritySQL->getInfo($transactionInfo['authority_id']);

        $completeName = $this->namesGenerator->createCompleteName(
            $transactionInfo['siren'],
            $this->fichierCompteur->getNumero()
        );
        $this->heliosTransactionsSQL->setCompleteName($transaction_id, $completeName);
        $this->logger->info("Nom du fichier à envoyer : $completeName");

        $file_path = $this->pesAllerResolver->getFullPath($transaction_id);
        $this->cloudPesAllerStorage->downloadFileFromCloud($transaction_id);

        $file_path_with_complete_name = $file_sending_repository . '/' . $completeName;
        if (! copy($file_path, $file_path_with_complete_name)) {
            $this->logger->error("Transaction $transaction_id : échec de la copie...: cp $file_path $file_path_with_complete_name");
            return;
        }
        if (HELIOS_ZIP_BEFORE_SEND) {
            $file_to_send = $file_sending_repository . "/" . $transactionInfo['sha1'] . ".zip";
            $zipArchive = new ZipArchive();
            if (! $zipArchive->open($file_to_send, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
                $this->logger->error("Transaction $transaction_id: Impossible d'ouvrir $file_to_send");
                return;
            }
            $zipArchive->addFile($file_path_with_complete_name, $completeName);
            $zipArchive->close();
        } else {
            $file_to_send = $file_path_with_complete_name;
        }

        $sha1_file = sha1_file($file_path);
        if ($sha1_file != $transactionInfo['sha1']) {
            $message = "Transaction $transaction_id : le fichier a été altéré depuis son postage ou sa signature sur la plateforme\n";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            return;
        }

        try {
            $pesAllerData = $this->pesAllerReader->getPesAllerData($file_path);
            $p_msg = $this->namesGenerator->getP_MSGFromPesAllerData($pesAllerData);
        } catch (Exception $e) {
            $message = "Transaction $transaction_id : " . $e->getMessage();
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            if (HELIOS_ZIP_BEFORE_SEND) {
                unlink($file_to_send);
            }
            unlink($file_path_with_complete_name);
            return;
        }

        if (! $authorityInfo['helios_ftp_dest']) {
            $message = "Transaction $transaction_id : les propriétés Helios FTP ne sont pas configurées correctement";
            $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
            unlink($file_path_with_complete_name);
            return;
        }

        try {
            if ($authorityInfo['helios_use_passtrans'] != $usePasstrans) {
                $message = "Transaction $transaction_id : la transaction a été aiguillée sur la mauvaise file passtrans";
                $this->updateStatus($transaction_id, HeliosTransactionsSQL::ERREUR, $message, $transactionInfo['user_id']);
                unlink($file_path_with_complete_name);
                return;
            }
            $this->heliosConnectionsConfigurationManager
                ->get($usePasstrans)->sendFileOnUniqueConnection($authorityInfo['helios_ftp_dest'], $p_msg, $file_to_send);
        } catch (Exception $e) {
            $this->logger->error("Transaction $transaction_id: Erreur lors du postage de la transaction Helios $transaction_id : " . $e->getMessage());
            unlink($file_path_with_complete_name);
            return;
        }

        $passtransMessage = $usePasstrans ? ' [Passtrans]' : '';
        $message = "Transaction $transaction_id transmise au serveur." . $passtransMessage;
        $this->updateStatus(
            $transaction_id,
            $this->getStatutApresTransmission($pesAllerData->isPesAcquitRetour),
            $message,
            $transactionInfo['user_id']
        );

        $this->heliosTransmissionWindowsSQL->addFile($transactionInfo['file_size']);

        if (HELIOS_ZIP_BEFORE_SEND) {
            unlink($file_to_send);
        }
        unlink($file_path_with_complete_name);
    }

    private function getStatutApresTransmission(bool $isPesAcquitRetour): int
    {
        if ($isPesAcquitRetour) {
            return HeliosTransactionsSQL::TRANSMIS_SANS_ACK;
        }
        return HeliosTransactionsSQL::TRANSMIS;
    }


    public function rollback($transaction_id)
    {
    }

    public function extratInfoFromPESAller(SimpleXMLElement $pes_xml): array
    {
        $info_pes['nom_fic'] = strval($pes_xml->Enveloppe->Parametres->NomFic['V']);
        $info_pes['cod_col'] = strval($pes_xml->EnTetePES->CodCol['V']);
        $info_pes['cod_bud'] = strval($pes_xml->EnTetePES->CodBud['V']);
        $info_pes['id_post'] = strval($pes_xml->EnTetePES->IdPost['V']);
        return $info_pes;
    }


    /**
     * Les fichiers qui ne contiennent ni bordereau, ni PJ, ni marché ne généère pas d'acquittement
     * et donc ne sont jamais ni acquitter ni en erreur
     * @param SimpleXMLElement $pes_xml
     * @return bool
     */
    public function isPESEmpty(SimpleXMLElement $pes_xml): bool
    {
        $r = [];
        foreach ($pes_xml->children() as $child) {
            $r[] = $child->getName();
        }
        $r = array_diff($r, ['Enveloppe','EnTetePES']);
        return ! boolval($r);
    }
}
