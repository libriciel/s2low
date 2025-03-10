<?php

namespace S2low\Command\Helios;

use LogicException;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnector;
use S2lowLegacy\Lib\HeliosNamesGenerator;
use S2lowLegacy\Lib\PesAllerReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Permet de tester la connection aux serveurs ( Gateway et Passtrans ) de la DGFiP
 */
class TestDGFiPConnectionCommand extends Command
{
    private DGFiPConnectionsManager $DGFiPConnectionsManager;
    private DGFiPConnectionBuilder $connectionBuilder;

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionsManager $DGFiPConnectionsManager
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder $connectionBuilder
     */
    public function __construct(
        DGFiPConnectionsManager $DGFiPConnectionsManager,
        DGFiPConnectionBuilder $connectionBuilder,
        private PesAllerReader $pesAllerReader,
        private HeliosNamesGenerator $producer
    ) {
        parent::__construct();
        $this->DGFiPConnectionsManager = $DGFiPConnectionsManager;
        $this->connectionBuilder = $connectionBuilder;
    }

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            ->setName('test:test-dgfip-connection')
            ->setDescription(
                'Tests actes notification sending'
            )
            ->addOption(
                'usePasstrans',
                null,
                InputOption::VALUE_NONE,
                'Utilise les valeurs renseignées par la connection passtrans. Par défaut, les valeurs Gateway sont utilisées.'
            )
            ->addOption(
                'server',
                null,
                InputOption::VALUE_REQUIRED,
                'serveur'
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_REQUIRED,
                'port'
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_REQUIRED,
                'port'
            )
            ->addOption(
                'login',
                null,
                InputOption::VALUE_REQUIRED,
                'login'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'password'
            )
            ->addOption(
                'response_server_path',
                null,
                InputOption::VALUE_REQUIRED,
                'InputOption::VALUE_REQUIRED'
            )
            ->addOption(
                'downloadFile',
                null,
                InputOption::VALUE_REQUIRED,
                'download file'
            )
            ->addOption(
                'listFiles',
                null,
                InputOption::VALUE_NONE,
                'List Available files on response_server_path'
            )
            ->addOption(
                'testUpload',
                null,
                InputOption::VALUE_NONE,
                'List Available files on response_server_path'
            )
            ->addOption(
                'pathToFileToUpload',
                null,
                InputOption::VALUE_REQUIRED,
                'path to file to upload',
                __DIR__ . '/../../../test/PHPUnit/helios/fixtures/pes_acquit.xml'
            )
            ->addOption(
                'p_dest',
                null,
                InputOption::VALUE_REQUIRED,
                'p_dest',
                'VHQCE31'
            )
            ->addOption(
                'pAppli',
                null,
                InputOption::VALUE_REQUIRED
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     * @throws \Exception
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // TODO : permettre de configurer usePasstrans
        $defaultConfiguration = $this->DGFiPConnectionsManager->getDGFipConnectionConfiguration(
            $input->getOption('usePasstrans')
        );

        $overridenConfiguration = new DGFiPConnectionConfiguration(
            $input->getOption('server') ?? $defaultConfiguration->getServer(),
            $input->getOption('port') ?? $defaultConfiguration->getPort(),
            $input->getOption('login') ?? $defaultConfiguration->getLogin(),
            $input->getOption('password') ?? $defaultConfiguration->getPassword(),
            $input->getOption('mode') ?? $defaultConfiguration->getMode()->getMode(),
            $defaultConfiguration->isPassiveMode(),
            $defaultConfiguration->getSendingDestination(),
            $input->getOption('response_server_path') ?? $defaultConfiguration->getResponseServerPath(),
            $defaultConfiguration->getHeliosFtpAppli(),
        );

        /** @var DGFiPConnector $connector */
        $connector = $this->connectionBuilder->getConnector($overridenConfiguration);

        $io->title("Connection à {$connector->getURL()}");

        $connector->connect();

        $io->success(
            'Connecté'
        );
        $files = $connector->getFileNames($overridenConfiguration->getResponseServerPath());    // Nécessaire, c'est à cet endroit que se
        // fait le chdir
        // TODO : permettre de lister aussi le répertoire d'entrée...
        // Attention : le getResponseServerPath est basé sur un chdir, l'ordre est important.
        // il faudrait le faire avant le listage du répertoire de sort

        if ($input->getOption('listFiles')) {
            $io->title("Liste des fichiers présents sur {$overridenConfiguration->getResponseServerPath()}");
            $io->listing($files);
        }
        if (!is_null($input->getOption('downloadFile'))) {
            $io->title("Téléchargement de {$input->getOption('downloadFile')}\n");
            $tmp = sys_get_temp_dir() . '/test_' . date('Ymdhis') . '_' . mt_rand();
            $connector->retrieveFile($tmp, $input->getOption('downloadFile'));   //TODO : test return
            $io->success("Disponible $tmp");                                                      // value
        }

        if ($input->getOption('testUpload')) {
            $file_path = $input->getOption('pathToFileToUpload');
            if (! file_exists($file_path)) {
                throw new \Exception("fichier $file_path non trouvé");
            }
            $p_dest = $input->getOption('p_dest');
            $pAppli = $input->getOption('pAppli') ?? $defaultConfiguration->getHeliosFtpAppli();

            $p_msg = $this->producer->getP_MSGFromPesAllerData($this->pesAllerReader->getPesAllerData($file_path));

            $io->title("Envoi de  de {$file_path}\n");
            $io->definitionList(
                "Paramètres",
                ["p_dest" => $p_dest],
                ["pAppli" => $pAppli],
                ["p_msg" => $p_msg]
            );

            $connector->sendOneFileWithProperties(
                $p_dest,
                $p_msg,
                $pAppli,
                $overridenConfiguration->getSendingDestination(),
                $file_path
            );
        }

        $connector->close();
        return 0;
    }
}
