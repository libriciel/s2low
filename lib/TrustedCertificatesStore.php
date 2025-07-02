<?php

namespace S2lowLegacy\Lib;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

class TrustedCertificatesStore
{
    public function __construct(
        private string $rootPath,
        private PemCertificateFactory $pemCertificateFactory,
        private LoggerInterface $logger
    ) {
        if (!is_dir($this->rootPath)) {
            throw new RuntimeException("Root path '{$this->rootPath}' does not exist");
        }
    }

    private function getCertificatesFileNames(): array
    {
        $availableCertificatesFileNames = [];
        foreach (glob($this->rootPath . '/*.pem') as $certificateFileName) {
            if (!preg_match('/\_CRL.pem$/', $certificateFileName)) {
                $availableCertificatesFileNames[] = $certificateFileName;
            }
        }
        return $availableCertificatesFileNames;
    }

    public function getAvailableCertificates(): array
    {
        $availableCertificates = [];
        foreach ($this->getCertificatesFileNames() as $certificateFileName) {
            try {
                $availableCertificates[] = $this->pemCertificateFactory
                    ->getFromString(file_get_contents($certificateFileName));
            } catch (Exception $e) {
                $this->logger->critical("[$certificateFileName] : " . $e->getMessage());
            }
        }
        return $availableCertificates;
    }
}
