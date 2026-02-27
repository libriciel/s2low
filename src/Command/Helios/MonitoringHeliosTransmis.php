<?php

//Script charge de verifier que le plus vieux vlux à l'état transmis
//n'a pas plus de 4h

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal

namespace S2low\Command\Helios;

use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonitoringHeliosTransmis extends Command
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
            ->setName('helios:monitoring-helios-transmis-4h')
            ->setDescription(
                "helios:monitoring-helios-transmis-4h"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        $email = EMAIL_ADMIN_TECHNIQUE;
        $subject = "Transaction helios a l etat transmis";

        $retour = 0;
        $message = "OK";
        $limit = 300;
        $last_status = "3";
        $timestamp = time() - (60 * 60);
        $timestampmax = time() - (4 * 60 * 60);
        $sql = "SELECT count(*) " .
            "FROM helios_transactions " .
            "WHERE helios_transactions.last_status_id = '" . $last_status . "' " .
            "AND DATE_TRUNC('minute',helios_transactions.submission_date) < DATE_TRUNC('minute',TIMESTAMP '" . date("Y-m-d H:i:s", $timestamp) . "') " .
            "AND DATE_TRUNC('minute',helios_transactions.submission_date) > DATE_TRUNC('minute',TIMESTAMP '" . date("Y-m-d H:i:s", $timestampmax) . "') ";

#echo "$sql \n";

        $nb_transac = $this->sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

        if ($nb_transac > $limit) {
            $message = "CRITICAL";
            $retour = 2;
            $mailMessage = "ATTENTION : $nb_transac transactions a etat transmis sur S2LOW depuis " . date("Y-m-d H:i:s", $timestampmax) . ". La limite est a $limit helios a etat transmis.";
            $mail = $this->mailerSymfonyFactory->getInstance();
            $mail->addRecipient($email);
            $mail->sendMail($subject, $mailMessage);
        }

        echo "$message - $nb_transac etat transmis depuis " . date("Y-m-d H:i:s", $timestampmax) . "\n";
        exit($retour);
    }
}
