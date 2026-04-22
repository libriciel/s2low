<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\TmpFolder;
use Exception;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Model\AuthoritySQL;
use Twig\Environment;

class ActesNotification
{
    private ActesTransactionsSQL $actesTransactionsSQL;
    private ActeTamponne $acteTamponne;
    private AuthoritySQL $authoritySQL;
    private ActesEnvelopeSQL $actesEnveloppeSQL;

    private MailerSymfonyFactory $mailerFactory;

    private LoggerInterface $logger;

    private string $actes_appli_trigramme;

    private ActesRetriever $actesRetriever;
    /**
     * @var BordereauPdfGenerator
     */
    private BordereauPdfGenerator $bordereauPdfGenerator;
    /**
     * @var \Twig\Environment
     */
    private Environment $twig;
    private bool $useProdNotifications;

    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        ActeTamponne $acteTamponne,
        AuthoritySQL $authoritySQL,
        ActesEnvelopeSQL $actesEnveloppeSQL,
        MailerSymfonyFactory $mailerFactory,
        LoggerInterface $logger,
        string $actes_appli_trigramme,
        ActesRetriever $actesRetriever,
        BordereauPdfGenerator $bordereauPdfGenerator,
        Environment $twigEnvironment,
        bool $use_prod_notifications
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->acteTamponne = $acteTamponne;
        $this->authoritySQL = $authoritySQL;
        $this->actesEnveloppeSQL = $actesEnveloppeSQL;
        $this->mailerFactory = $mailerFactory;
        $this->logger = $logger;
        $this->actes_appli_trigramme = $actes_appli_trigramme;
        $this->actesRetriever = $actesRetriever;
        $this->bordereauPdfGenerator = $bordereauPdfGenerator;
        $this->twig = $twigEnvironment;
        $this->useProdNotifications = $use_prod_notifications;
    }

    /**
     * @throws Exception
     */
    public function sendAutomaticNotification(): void
    {
        $sigtermHandler = SigTermHandler::getInstance();
        $transactionsToNotify = $this->actesTransactionsSQL->getTransactionToAutoBroadcast();
        foreach ($transactionsToNotify as $transaction_id) {
            $this->logger->info("Notification de la transaction $transaction_id");
            $this->sendNotificationManuel($transaction_id);
            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
        }
    }

    /**
     * @param $transactionId
     * @return bool
     * @throws Exception
     */
    public function sendNotificationManuel($transactionId): bool
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        try {
            $transactionInfo = $this->actesTransactionsSQL->getInfo($transactionId);
            if (!$transactionInfo) {
                throw new Exception("La transaction $transactionId n'existe pas");
            }
            $this->sendNotification($transactionInfo, $tmp_folder);
            $tmpFolder->delete($tmp_folder);
        } catch (Exception $e) {
            $tmpFolder->delete($tmp_folder);
            throw $e;
        }
        return true;
    }

    /**
     * @param array $transaction_info
     * @param $tmp_folder
     * @throws Exception
     */
    private function sendNotification(array $transaction_info, $tmp_folder): void
    {
        $authority_info = $this->authoritySQL->getInfo($transaction_info['authority_id']);
        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);
        $defaultBroadcastEmail = explode(',', $authority_info['default_broadcast_email'] ?? '');

        $broadcast_emails = $transaction_info['broadcast_emails'] ?? '';
        $brodcastEmail = explode(',', $broadcast_emails);
        $brodcastEmail = array_diff($brodcastEmail, $defaultBroadcastEmail);
        $archive_path = $this->actesRetriever->getPath($envelope_info['file_path']);

        try {
            $fichiers_tamponnees = $this->tamponnerTGZ($archive_path, $transaction_info, $tmp_folder);
        } catch (Exception $e) {
            $this->logger->warning("[{$transaction_info['envelope_id']}] Erreur lors de la décompression");
            $this->logger->warning("[{$transaction_info['envelope_id']}] {$e->getMessage()}");
            $fichiers_tamponnees = [];
        }

        if (!$transaction_info['auto_broadcasted']) {
            //envoie du mail au proprietaire de l'acte
            $this->sendMail($transaction_info, $envelope_info['email'], true, $authority_info['new_notification'], $fichiers_tamponnees);
            //envoie du mail a toutes les adresses renseignees dans defaut

            foreach ($defaultBroadcastEmail as $email) {
                $this->sendMail($transaction_info, $email, true, false, $fichiers_tamponnees);
            }
            $this->actesTransactionsSQL->setAutoBroadcasted($transaction_info['id']);
        }
        if ($broadcast_emails) {
            foreach ($brodcastEmail as $email) {
                $this->sendMail($transaction_info, $email, $transaction_info['broadcast_send_sources'] == 1, false, $fichiers_tamponnees);
            }
            $this->actesTransactionsSQL->setBroadcasted($transaction_info['id']);
        }
    }

    /**
     * @throws \Exception
     */
    private function sendMail($transactionInfo, $email, $withFile, $add_url_recup, array $fichiers_tamponnees): void
    {

        if (!$email) {
            return;
        }

        $mailer = $this->mailerFactory->getInstance();

        $err = $mailer->addRecipient($email);
        if (!$err) {
            $this->logger->info("$email invalide !");
        }
        $status_info = $this->actesTransactionsSQL->getStatusInfo($transactionInfo['id'], 4);
        $flux_retour = $this->actesTransactionsSQL->getStatusInfoWithFluxRetour($transactionInfo['id'], 4);
        if ($status_info && $flux_retour) {
            $ar_actes_filename = "{$transactionInfo['unique_id']}-{$transactionInfo['type']}-{$transactionInfo['id']}-reponse.xml";
            $mailer->addStringAsFile($ar_actes_filename, $flux_retour['flux_retour']);

            $bordereauPdf = $this->bordereauPdfGenerator
                ->generate($transactionInfo['id'], "bordereau_acquittement.pdf", true, "S");



            $mailer->addStringAsFile("bordereau_acquittement.pdf", $bordereauPdf);
            if ($withFile && !$add_url_recup) {
                foreach ($fichiers_tamponnees as $fichier) {
                    $mailer->addFile($fichier);
                }
            }
        }
        $mailContent = $this->getMailContent($transactionInfo, $add_url_recup, $this->useProdNotifications);
        $mailText = $this->twig->render('mailtextenotificationacte.twig', $mailContent);
        $mailHtml = $this->twig->render('mailnotificationacte.html.twig', $mailContent);
        $authority_info = $this->authoritySQL->getInfo($transactionInfo['authority_id']);
        $mailSubject = "[{$authority_info['name']}] Notification concernant l'acte " . $transactionInfo['number'];
        if (!$this->useProdNotifications) {
            $mailSubject = "[SIMULATION] $mailSubject";
        }
        $mailer->sendMailWithHtml(
            $mailSubject,
            $mailText,
            $mailHtml
        );

        $message_log =
            sprintf(
                "[%s] Transaction %s (%d) : Envoi d'une notification à %s. Numéro SIREN de la collectivité : %s. Type de transaction: %d",
                $this->actes_appli_trigramme,
                $transactionInfo['unique_id'],
                $transactionInfo['id'],
                $email,
                $authority_info['siren'],
                $transactionInfo['type']
            );
        Log::newEntry(LOG_ISSUER_NAME, $message_log, 1, false, "USER", "actes", false, $transactionInfo['user_id']);
    }

    /**
     * @throws \Twig\Error\SyntaxError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\LoaderError
     */
    private function getMailContent($transaction_info, $add_url_recup, $useProdNotifications): array
    {

        $last_status_id = $transaction_info['last_status_id'];

        if ($last_status_id == ActesStatusSQL::STATUS_ACQUITTEMENT_RECU) {
            $status_info = $this->actesTransactionsSQL->getStatusInfo($transaction_info['id'], $last_status_id);
        } else {
            $status_info = $this->actesTransactionsSQL->getStatusInfo($transaction_info['id'], ActesStatusSQL::STATUS_DOCUMENT_RECU);
        }

        $envelope_info = $this->actesEnveloppeSQL->getInfo($transaction_info['envelope_id']);

        return [
            "last_status_id" => $last_status_id,
            "number" => $transaction_info['number'],
            "type" => $transaction_info['type'],
            "unique_id" => $transaction_info['unique_id'],
            "nature_descr" => $transaction_info['nature_descr'],
            "subject" => $transaction_info['subject'],
            "decision_date" => $transaction_info['decision_date'],
            "url" => \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_show.php?id=") . $transaction_info['id'],
            "archive_url" => $transaction_info['archive_url'],
            "date" => $status_info['date'],
            "submission_date" => $envelope_info['submission_date'],
            "add_url_recup" => $add_url_recup,
            "isProd" => $useProdNotifications
        ];
    }


    /**
     * @param $filePath
     * @param $transactionInfo
     * @param $tmp_folder
     * @return array
     * @throws Exception
     */
    private function tamponnerTGZ($filePath, $transactionInfo, $tmp_folder): array
    {

        $command = "tar xzf $filePath --directory $tmp_folder 2>&1";
        $this->logger->debug("Executing comand : $command");
        exec($command, $output, $return_var);
        if ($return_var != 0) {
            throw new Exception("Erreur ($return_var) lors de la décompression de l'archive $filePath : " . implode("\n", $output));
        }

        $result = array();
        $files = array_diff(scandir($tmp_folder), array('.', '..'));
        foreach ($files as $file) {
            $file = $tmp_folder . "/$file";
            if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                $fileString = $this->acteTamponne->tamponnerPDF($file, $transactionInfo['id']);
                file_put_contents($file, $fileString);
            }
            $result[] = $file;
        }

        return $result;
    }
}
