<?php

namespace S2lowLegacy\Lib;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

class TrustedCertificatesStore
{
    public function __construct(
        private PemCertificateFactory $pemCertificateFactory,
        private LoggerInterface $logger
    ) {
    }

    private function getCertificatesFileNames(string $rootPath): array
    {
        if (!is_dir($rootPath)) {
            throw new RuntimeException("Root path '{$rootPath}' does not exist");
        }
        $availableCertificatesFileNames = [];
        foreach (glob($rootPath . '/*.pem') as $certificateFileName) {
            if (!preg_match('/\_CRL.pem$/', $certificateFileName)) {
                $availableCertificatesFileNames[] = $certificateFileName;
            }
        }
        return $availableCertificatesFileNames;
    }

    public function getAvailableCertificates(string $rootPath): array
    {
        $availableCertificates = [];
        foreach ($this->getCertificatesFileNames($rootPath) as $certificateFileName) {
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
