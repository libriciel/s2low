<?php

namespace S2low\Command\Admin;

use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RapportHistoriqueCollectivite extends Command
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
            ->setName('admin:rapport-historique-collectivite')
            ->setDescription(
                "admin:rapport-historique-collectivite"
            )
            ->addArgument(
                'authority_id',
                InputArgument::REQUIRED,
                "authority_id"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
// Paramètre attendu id coll
// Retourne la taille du dossier actes et la taille des flux PES
        $authority_id = $input->getArgument('authority_id');

        $sql = "SELECT name FROM authorities WHERE id = ?";
        $namecoll = $this->sqlQuery->queryOne($sql, $authority_id);


        $sql = "SELECT siren FROM authorities WHERE id= ?";
        $siren = $this->sqlQuery->queryOne($sql, $authority_id);
        echo "$siren\n";

        $message = "---------------------------------\n" .
            "id : $authority_id \n" .
            "nom de la collectivite : $namecoll\n";
// ACTES

        $sql = "SELECT sum(file_size),count(*) FROM actes_envelopes WHERE siren like ?";
        $actes = $this->sqlQuery->query($sql, $siren);
//var_dump($actes);exit;
        if ($actes[0]["sum"] > 0) {
            $factor = (int)(log($actes[0]["sum"], 1000));
            $units = 'BKMGTP';
            $sizeactes = sprintf("%.2f", $actes[0]["sum"] / pow(1000, $factor));

            echo "taille actes : " . $sizeactes . " et nombre d'enveloppes : " . $actes[0]["count"] . "\n";
            $message .= "Taille totale des enveloppes actes : " . $sizeactes . " et nombre d'enveloppes " . $actes[0]["count"] . "\n";
        } else {
            $message .= "Il n'y a pas d'actes\n";
        }

// HELIOS
        $sql = "select sum(file_size),count(*) FROM helios_transactions WHERE authority_id= ?";
        $helios = $this->sqlQuery->query($sql, $authority_id);
//var_dump($helios);
        if ($helios[0]["sum"] > 0) {
            $factor = (int)(log($helios[0]["sum"], 1000));
            $units = 'BKMGTP';
            $sizehelios = sprintf("%.2f", $helios[0]["sum"] / pow(1000, $factor));

            echo "taille helios : " . $sizehelios . " et nombre de fichiers : " . $helios[0]["count"] . "\n";

            $message .= "Taille totale des flux PES_ALLER : $sizehelios et le nombre de PES " . $helios[0]["count"];
        } else {
            $message .= "Il n'y a pas de PES_ALLER\n";
        }

        $subject = "Rapport taille historique collectivite $namecoll";
        $mail = $this->mailerSymfonyFactory->getInstance();
        $mail->addRecipient(EMAIL_ADMIN_TECHNIQUE); // L'envoi ne fonctionne pas
        $mail->sendMail($subject, $message);                // avec EMAIL_ADMIN_TECHNIQUE = tedetis@localhost
        return 0;
    }
}
