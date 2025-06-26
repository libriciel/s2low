<?php

namespace S2low\Command\Admin;

use Exception;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Lib\CertificateChainsBuilder;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\TrustedCertificatesStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidCaInspector extends Command
{
    public function __construct(
        private PemCertificateFactory $pemCertificateFactory,
        private LoggerInterface $logger,
        private CertificateChainsBuilder $certificateChainsBuilder
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('admin:validca-inspector')
            ->setDescription(
                "Permet de repérer les erreurs du validca"
            )
            ->addArgument(
                'validca_dir',
                InputArgument::REQUIRED,
                "Répertoire contenant le valid_ca à diagnostiquer"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $trustedCertificatesStore = new TrustedCertificatesStore(
            $input->getArgument('validca_dir'),
            $this->pemCertificateFactory,
            $this->logger
        );

        $availableCertificates = $trustedCertificatesStore->getAvailableCertificates();

        $certificateChains = $this->certificateChainsBuilder->build($availableCertificates);

        foreach ($certificateChains as $certificateChain) {
            /** @var \S2lowLegacy\Lib\CertificateChain $certificateChain */
            $io->title($certificateChain->getLeafSubjectDN()['CN']);
            try {
                $certificateChain->checkValidity();
            } catch (Exception $e) {
                $io->error($e->getMessage());
            }
            foreach ($certificateChain->getCertificates() as $certificate) {
                $output->writeln('   ' . $certificate->getSubjectDN()['CN']);
            }
            $io->newLine();
        }
        return Command::SUCCESS;
    }
}
