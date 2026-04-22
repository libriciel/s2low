<?php

namespace S2low\Command;

use Psr\Log\LoggerInterface;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Model\LogsHistoriqueSQL;
use S2lowLegacy\Model\LogsRequestData;
use S2lowLegacy\Model\LogsRequestSQL;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

class JournalRequestCommand extends Command
{
    /**
     * @var \S2lowLegacy\Model\LogsHistoriqueSQL
     */
    private LogsHistoriqueSQL $logsHistoriqueSQL;
    /**
     * @var \S2lowLegacy\Model\LogsRequestSQL
     */
    private LogsRequestSQL $logsRequestSQL;
    /**
     * @var \S2low\Services\MailActesNotifications\MailerSymfonyFactory
     */
    private MailerSymfonyFactory $mailerSymfonyFactory;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var \S2lowLegacy\Model\UserSQL
     */
    private UserSQL $userSQL;

    public function __construct(
        LogsHistoriqueSQL $logsHistoriqueSQL,
        LogsRequestSQL $logsRequestSQL,
        UserSQL $userSQL,
        LoggerInterface $s2lowLogger,
        MailerSymfonyFactory $mailerSymfonyFactory
    ) {
        $this->logsHistoriqueSQL = $logsHistoriqueSQL;
        $this->logsRequestSQL = $logsRequestSQL;
        $this->userSQL = $userSQL;
        $this->logger = $s2lowLogger;
        $this->mailerSymfonyFactory = $mailerSymfonyFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cron:journal-request')
            ->setDescription(
                "Notifies actes"
            )
            ->addArgument(
                'minimumExecutionTime',
                InputArgument::OPTIONAL,
                "minimum execution time",
                60
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = time();
        if (!is_numeric($input->getArgument('minimumExecutionTime'))) {
            throw new UnexpectedValueException("minimumExecutionTime should be an integer");
        }
        $min_exec_time = (int)$input->getArgument('minimumExecutionTime');
        $all_request = $this->logsRequestSQL->getAllByState(LogsRequestData::STATE_ASKING);
        echo count($all_request) . " requêtes en attente...";
        $sigtermHandler = SigTermHandler::getInstance();
        foreach ($all_request as $request) {
            echo "Traitement de la requête {$request['id']}\n";
            $logsRequestData = new LogsRequestData();
            $logsRequestData->date_debut = $request['date_debut'];
            $logsRequestData->date_fin = $request['date_fin'];
            $logsRequestData->authority_group_id = $request['authority_group_id'];
            $logsRequestData->authority_id = $request['authority_id'];
            $logsRequestData->user_id = $request['user_id'];
            $logsRequestData->user_id_demandeur = $request['user_id_demandeur'];

            $output_filename = EXPORT_LOGS_DIRECTORY . "/{$request['id']}.csv";

            $this->logsHistoriqueSQL->request($logsRequestData, $output_filename);
            $this->logsRequestSQL->setAvailable($request['id']);

            $user_id_demandeur = $request['user_id_demandeur'];

            $user_info = $this->userSQL->getInfo($user_id_demandeur);

            $messageMail = "Bonjour,\nVotre fichier contenant les lignes du journal est disponible sur " .
                \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/common/logs_request_view.php") . "\n\nCelui-ci est disponible pendant 24 heures.\n\nCordialement.\n";

            $mail = $this->mailerSymfonyFactory->getInstance();
            $mail->addRecipient($user_info['email']);
            $mail->sendMail("[S2LOW] Journal disponible", $messageMail);

            if ($sigtermHandler->isSigtermCalled()) {
                break;
            }
        }

        $old_request = $this->logsRequestSQL->getOldRequest();
        foreach ($old_request as $request) {
            echo "Suppresion de la requête {$request['id']}\n";
            $this->logsRequestSQL->forceDelete($request['id']);
            unlink(EXPORT_LOGS_DIRECTORY . "/{$request['id']}.csv");
        }
        $stop = time();
        $sleep = $min_exec_time - ($stop - $start);
        if ($sleep > 0) {
            $this->logger->info("Arret du script : $sleep");
            sleep($sleep);
        }
        return 0;
    }
}
