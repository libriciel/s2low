<?php

// Script chargÃ© de sortir le nombre d'envellopes du type 1 et 6 sur les 4 derniÃ¨res heures
// Ne prend pas de paramÃ¨tre obligatoire
// Si le mot 'telegraf' est mis en paramÃ¨tre, la sortie du script sera au format Influxdb

// Sortie par dÃ©faut au format Nagios
//RETOURNE 0 si tout va bien
//RETOURNE 2 si le plus vieil acte ï¿½ l'ï¿½tat postï¿½ ï¿½ plus d'une heure
namespace S2low\Command\Actes;

use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonitoringActesTransmis extends Command
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
            ->setName('cron:monitoring-actes-transmis-4h')
            ->setDescription(
                "cron:monitoring-actes-transmis-4h"
            )
            ->addArgument(
                'outputType',
                InputArgument::OPTIONAL,
                "if telegraph, output formatted for telegraph"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = EMAIL_ADMIN_TECHNIQUE;
        $subject = "Transaction actes a l etat transmis";

        $retour = 0;
        $message = "OK";
        $limit = 50;
        $last_status = "3";
        $timestamp = time();
        $timestampmax = time() - (4 * 60 * 60);
        $sql = "SELECT count(actes_envelopes.id) " .
            "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id " .
            "WHERE actes_transactions.last_status_id = '" . $last_status . "' " .
            "AND actes_envelopes.submission_date < '" . date("Y-m-d H:i:s", $timestamp) . "' " .
            "AND actes_envelopes.submission_date > '" . date("Y-m-d H:i:s", $timestampmax) . "' " .
            "AND (actes_transactions.type = '1' OR actes_transactions.type = '6') ";

#echo "$sql \n";

        $nb_transac = $this->sqlQuery->queryOne($sql);

        if (!is_null($input->getArgument("outputType"))) {
            if ($input->getArgument("outputType") == "telegraf") {
                echo "actes_transaction,status=transmislimit,application=s2low nb_transac=$nb_transac";
            }

            exit;
        } elseif ($nb_transac > $limit) {
            $message = "CRITICAL";
            $retour = 2;
            $mailMessage = "ATTENTION : $nb_transac transactions a etat transmis sur S2LOW depuis " . date("Y-m-d H:i:s", $timestampmax) . ". La limite est a $limit actes a etat transmis.";
            $mail = $this->mailerSymfonyFactory->getInstance();
            $mail->addRecipient($email);
            $mail->sendMail($subject, $mailMessage);
        }

        echo "$message - $nb_transac etat transmis depuis " . date("Y-m-d H:i:s", $timestampmax) . "\n";
        exit($retour);
    }
}
