<?php

namespace S2low\Services;

use phpseclib3\Exception\FileNotFoundException;
use S2low\Exceptions\TransactionNotFoundException;

/**
 * @description Cette interface permet de définir la methode commune de recuperation du chemin relatif pour un fichier metier donné.
 * Si un nouveau fichier metier doit etre stocké dans le cloud, cette interface devra etre implémenté sur une classe qui pourra retourner le chemin.
 * (Voir la config "Stockage Enveloppe Acte" dans le 'services.yaml' pour exemple)
 */
interface FileDataProvider
{
    /**
     * @throws TransactionNotFoundException
     * @throws FileNotFoundException
     */
    public function getRelativePath(string $transactionId): string;

    /**
     * @throws TransactionNotFoundException
    */
    public function getCloudId(string $transactionId): string;

    public function getTransactionIdFromFileName(string $filePath): ?string;

    public function setTransactionIsInCloud(string $transactionId): void;
}
