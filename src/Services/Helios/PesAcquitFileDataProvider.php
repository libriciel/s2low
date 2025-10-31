<?php

namespace S2low\Services\Helios;

use phpseclib3\Exception\FileNotFoundException;
use S2low\Exceptions\TransactionNotFoundException;
use S2low\Services\FileDataProvider;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class PesAcquitFileDataProvider implements FileDataProvider
{
    public function __construct(
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL
    ) {
    }

    /**
     * @Throws TransactionNotFoundException
     * @Throws FileNotFoundException
     */
    public function getRelativePath(string $transactionId): string
    {
        $heliosTransaction = $this->heliosTransactionsSQL->getInfo($transactionId);

        if ($heliosTransaction === false) {
            throw new TransactionNotFoundException('Aucune transaction Helios ne corresponds à l\'identifiant : [' . $transactionId . ']');
        }

        return $heliosTransaction['acquit_filename'];
    }

    public function getCloudId(string $transactionId): string
    {
        $heliosTransaction = $this->heliosTransactionsSQL->getInfo($transactionId);

        if ($heliosTransaction === false) {
            throw new TransactionNotFoundException('Aucune transaction Helios ne corresponds à l\'identifiant : [' . $transactionId . ']');
        }

        return $heliosTransaction['siren'] . '/' . $heliosTransaction['acquit_filename'];
    }

    public function getTransactionIdFromFileName(string $filePath): string
    {
        return (string) $this->heliosTransactionsSQL->getByPesAcquitName(basename($filePath));
    }

    public function setTransactionIsInCloud(string $transactionId): void
    {
        $this->heliosTransactionsSQL->setPesAcquitInCloud($transactionId, true);
    }
}
