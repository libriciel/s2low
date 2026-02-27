<?php

//Script charge de verifier que le plus vieil acte à l'état poste
//n'a pas plus de 30 minutes

//RETOURNE 0 si tout va bien
//RETOURNE 2 si le plus vieil acte à l'état posté à plus d'une heure
namespace S2low\Command\Actes;

use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MonitoringActesPoste extends Command
{
    /**
     * @var \S2lowLegacy\Lib\SQLQuery
     */
    private SQLQuery $sqlQuery;
    /**
     * @var \S2low\Services\MailActesNotifications\MailerSymfonyFactory
     */
    private MailerSymfonyFactory $mailerSymfonyFactory;

    public function __construct(SQLQuery $SQLQuery, MailerSymfonyFactory $mailerSymfonyFactory)
    {
        $this->sqlQuery = $SQLQuery;
        $this->mailerSymfonyFactory = $mailerSymfonyFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cron:monitoring-actes-postes-sup30min')
            ->setDescription(
                "cron:monitoring-actes-postes-sup30min"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = EMAIL_ADMIN_TECHNIQUE;
        $subject = "Transaction actes a l etat poste";

        $retour = 0;
        $message = "OK";
        $limit = 10;
        $last_status = "1";
        $timestamp = time() - (30 * 60);
        $sql = "SELECT count(*) " .
            "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id " .
            "WHERE actes_transactions.last_status_id = '" . $last_status . "' " .
            "AND DATE_TRUNC('minute',actes_envelopes.submission_date) < DATE_TRUNC('minute',TIMESTAMP '" . date("Y-m-d H:i:s", $timestamp) . "') " .
            "AND (actes_transactions.type like '1' OR actes_transactions.type like '6') ";

#echo "$sql \n";

        $nb_transac = $this->sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

        if ($nb_transac > $limit) {
            $message = "CRITICAL";
            $retour = 2;
            $mailMessage = "ATTENTION : $nb_transac transactions a etat poste sur S2LOW depuis plus de 30 minutes. La limite est a $limit actes a etat poste.";
            $mail = $this->mailerSymfonyFactory->getInstance();
            $mail->addRecipient($email);
            $mail->sendMail($subject, $mailMessage);
        }

        echo "$message - $nb_transac etat poste de plus de 30 min\n";
        exit($retour);
    }
}
