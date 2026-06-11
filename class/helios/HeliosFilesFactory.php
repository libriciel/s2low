<?php

namespace S2lowLegacy\Class\helios;

use S2low\Infrastructure\Directory;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HeliosFilesFactory
{
    public function __construct(
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        #[Autowire(service: 'app.heliosFilesUpload')]
        private readonly Directory $heliosFilesUpload,
        #[Autowire(service: 'app.heliosResponseDirectory')]
        private readonly Directory $heliosFilesResponse
    ) {
    }

    public function get($transaction_id): HeliosFilesNames
    {
        $info = $this->heliosTransactionsSQL->getInfo($transaction_id);
        return new HeliosFilesNames(
            $this->heliosFilesUpload->getPath($info['sha1']),                   // PES ALLER
            $this->heliosFilesUpload->getPath($info['complete_name']),          // PES ALLER utilisé lors de l'envoi, normalement supprimé
            $this->heliosFilesResponse->getPath($info['acquit_filename'])   // Acquit, utilisé par setAcquitFilename
        );
    }
}
