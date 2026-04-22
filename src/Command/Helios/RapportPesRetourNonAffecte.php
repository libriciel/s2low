<?php

namespace S2low\Command\Helios;

use Exception;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RapportPesRetourNonAffecte extends Command
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
            ->setName('helios:rapport-pes-retour-non-affecte')
            ->setDescription(
                "helios:rapport-pes-retour-non-affecte"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        libxml_use_internal_errors(true);

// Script charge de récuprer la liste distincte des SIRET des PES_RETOUR
// dans le dossier HELIOS_RESPONSES_ERROR_PATH

        $file_list = scandir(HELIOS_RESPONSES_ERROR_PATH);

        $file_list = array_diff($file_list, array('..', '.'));

        if (!$file_list) {
            return 0;
        }

//var_dump($file_list);
        $list_siret = array();
        foreach ($file_list as $file) {
            try {
                $filepath = HELIOS_RESPONSES_ERROR_PATH . "/$file";
                //echo $filepath."\n";
                libxml_clear_errors();
                $xml = simplexml_load_file($filepath);
                if (!$xml) {
                    throw new Exception("Le fichier n'est pas bien formé (fichier ignoré)");
                }
                $root_name = mb_strtolower($xml->getName());

                if ($root_name == 'pes_retour') {
                    //echo "c'est un PES RETOUR\n";
                    //var_dump($xml->EnTetePES);exit;
                    $siret = strval($xml->EnTetePES->IdColl["V"]);
                    if ((!in_array($siret, $list_siret)) && (mb_strlen($siret) == 14)) {
                        array_push($list_siret, $siret);
                    }
                }
            } catch (Exception $e) {
                echo "ERREUR :" . $e->getMessage();
            }
        }

//var_dump($list_siret);

        $list_coll = array();

        $message = '';
        foreach ($list_siret as $siret) {
            $sql = "SELECT id,name FROM authorities WHERE id IN (SELECT authority_id FROM authority_siret WHERE siret like '" . $siret . "')";
            $authority_id = $this->sqlQuery->query($sql);
            $message .= "Le SIRET $siret est associe aux collectivites :\n";
            //var_dump($authority_id);

            $i = 0;
            foreach ($authority_id as $coll) {
                /*
                 $list_coll[$siret][$i]['id']=strval($coll['id']);
                 $list_coll[$siret][$i]['name']=strval($coll['name']);
                 */
                $message .= strval($coll['name']) . " Acces direct a la gestion de ses SIRET : " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/authorities/admin_authority_siret.php?id=" . strval($coll['id']) . "\n");
            }
            $message .= "\n -------------------------------------------------- \n";
        }
//print_r($list_coll);
        $message .= "Vous avez jusqu'à 11h59 pour traiter ces SIRET";
        //mail(EMAIL_ADMIN_TECHNIQUE, , $message, "from: " . EMAIL_ADMIN);
        $mail = $this->mailerSymfonyFactory->getInstance();
        $mail->addRecipient(EMAIL_ADMIN_TECHNIQUE);
        $mail->sendMail("Rapport sur les PES_RETOUR non affectable", $message);
        return 0;
    }
}
