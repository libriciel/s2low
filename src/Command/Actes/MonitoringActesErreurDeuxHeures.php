<?php

//Script charge de verifier que le nombre d'acte revenu en erreur ne dépasse pas un seuil
//sur une tranche horaire

//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
namespace S2low\Command\Actes;

use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MonitoringActesErreurDeuxHeures extends Command
{
    public function __construct(
        private readonly SQLQuery $sqlQuery,
        private readonly MailerSymfonyFactory $mailerSymfonyFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('actes:monitoring-actes-erreur-2h')
            ->setDescription(
                "monitoring-actes-erreur-2h"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = EMAIL_ADMIN_TECHNIQUE;
        $subject = "Transaction actes a l etat erreur";

        $retour = 0;
        $message = "OK";
        $limit = 75;
        $last_status = "-1";
        $timestamp = time();
        $timestampmax = time() - (2 * 60 * 60);
        $sql = "SELECT count(actes_envelopes.id) " .
            "FROM actes_envelopes INNER JOIN actes_transactions ON actes_envelopes.id = actes_transactions.envelope_id " .
            "WHERE actes_transactions.last_status_id = '" . $last_status . "' " .
            "AND actes_envelopes.submission_date < '" . date("Y-m-d H:i:s", $timestamp) . "' " .
            "AND actes_envelopes.submission_date > '" . date("Y-m-d H:i:s", $timestampmax) . "' " .
            "AND (actes_transactions.type = '1' OR actes_transactions.type = '6') ";

#echo "$sql \n";

        $nb_transac = $this->sqlQuery->queryOne($sql);

#echo "$nb_transac \n";

        if ($nb_transac > $limit) {
            $message = "CRITICAL";
            $retour = 2;
            $mailMessage = "ATTENTION : $nb_transac transactions sont en erreur sur S2LOW sur une periode de 2h. La limite est a $limit actes en erreur.";
            $mail = $this->mailerSymfonyFactory->getInstance();
            $mail->addRecipient($email);
            $mail->sendMail($subject, $mailMessage);
        }

        echo "$message - $nb_transac en erreur sur une periode de 2h\n";
        exit($retour);
    }
}
