<?php

namespace S2low\Command\Database;

use S2lowLegacy\Class\User;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** Copier les lignes ci-dessou et adapter a vos donnees pour executer la commande
 * ```php
  php bin/console db:create-admin \
  --name "Dupont" \
  --given-name "Jean" \
  --email "jean.dupont@example.com" \
  --cert-path "/data/tdt-workspace/Admin Test  - Administrateur S2low Test.pem"
 * ```
 **/
#[AsCommand(
    name: 'db:create-admin',
    description: ' Permet de creer un compte admin avec certificat.'
)]
class CreateAdminCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nom')
            ->addOption('given-name', null, InputOption::VALUE_REQUIRED, 'Prénom')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse email')
            ->addOption('cert-path', null, InputOption::VALUE_REQUIRED, 'Chemin du certificat PEM');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getOption('name');
        $givenname = $input->getOption('given-name');
        $email = $input->getOption('email');
        $certificat = $input->getOption('cert-path');

        $him = new User();

        $him->set("name", $name);
        $him->set("givenname", $givenname);
        $him->set("email", $email);
        $him->set("status", 1);
        $him->set("authority_id", 1);
        $him->set("role", 'SADM');

        $him->set("certFilePath", $certificat);
        if (!$him->save()) {
            $output->writeln("Erreur lors de l'enregistrement de l'utilisateur : " . $him->getErrorMsg());

            return Command::FAILURE;
        }

        $output->writeln("Utilisateur créé avec succès");

        return Command::SUCCESS;
    }
}
