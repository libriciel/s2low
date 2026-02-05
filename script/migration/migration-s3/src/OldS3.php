<?php

namespace App;

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;

class OldS3
{
    private S3ClientInterface $client;
    public function __construct(array $config)
    {
        $this->client = new S3Client(args: [
            'region'  => $_ENV['OLD_S3_REGION'],
            'version' => 'latest',
            'endpoint' => $_ENV['OLD_S3_ENDPOINT'],
            'credentials' => [
                'key'    => $_ENV['OLD_S3_ACCESS_KEY'],
                'secret' => $_ENV['OLD_S3_SECRET_KEY'],
            ],
        ]);
    }

    public function test($bucket, $key, bool $autoRestore = false)
    {
        try {
            // 1. Récupérer les métadonnées de l'objet
            $meta = $this->client->headObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            $storageClass = $meta['StorageClass'] ?? 'STANDARD';
            $restoreStatus = $meta['Restore'] ?? '';

            // Initialisation de la réponse par défaut
            $result = [
                'bucket' => $bucket,
                'key' => $key,
                'current_status' => 'autre'
            ];

            // 2. Vérifier si l'objet est archivé (GLACIER ou DEEP_ARCHIVE)
            $isArchived = in_array($storageClass, ['GLACIER', 'DEEP_ARCHIVE', 'GLACIER_IR']);

            if ($isArchived) {
                // Vérifier si une restauration est en cours ou terminée
                // Format typique de 'Restore': ongoing-request="true" ou ongoing-request="false", expiry-date="..."
                if (empty($restoreStatus)) {
                    $result['current_status'] = 'frozen';
                    return $result;
                }

                if (str_contains($restoreStatus, 'ongoing-request="true"')) {
                    $result['current_status'] = 'en attente de restoration';
                    return $result;
                }

                // Si ongoing-request="false", l'objet est temporairement disponible
            }

            // 3. Téléchargement si disponible (Standard ou Restauré)
            $object = $this->client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            // Vous pouvez traiter le corps ici (ex: $object['Body']->getContents())
            $result['current_status'] = 'telechargé';
            $result['content'] = $object['Body']; // Optionnel selon votre besoin

            return $result;

        } catch (S3Exception $e) {
            return [
                'bucket' => $bucket,
                'key' => $key,
                'current_status' => 'erreur: ' . $e->getAwsErrorMessage()
            ];
        }

        return $resp;
    }


    function getFile(string $bucket, string $key, string $localPath, bool $autoRestore = false): array
    {
        try {
            // 1. Récupérer les métadonnées (HeadObject)
            $meta = $this->client->headObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            $storageClass = $meta['StorageClass'] ?? 'STANDARD';
            $restoreHeader = $meta['Restore'] ?? '';

            $result = [
                'bucket' => $bucket,
                'key' => $key,
                'local_path' => $localPath,
                'current_status' => 'autre'
            ];

            // Vérification des classes d'archives (Glacier ou Deep Archive)
            $isArchived = in_array($storageClass, ['GLACIER', 'DEEP_ARCHIVE']);

            if ($isArchived) {
                // Cas : Objet "gelé" (non restauré)
                if (empty($restoreHeader)) {
                    if ($autoRestore) {
                        $this->client->restoreObject([
                            'Bucket' => $bucket,
                            'Key'    => $key,
                            'RestoreRequest' => [
                                'Days' => 7,
                                'GlacierJobParameters' => ['Tier' => 'Standard'],
                            ],
                        ]);
                        $result['current_status'] = 'en attente de restoration';
                    } else {
                        $result['current_status'] = 'frozen';
                    }
                    return $result;
                }

                // Cas : Restauration en cours (ongoing-request="true")
                if (str_contains($restoreHeader, 'ongoing-request="true"')) {
                    $result['current_status'] = 'en attente de restoration';
                    return $result;
                }

                // Si ongoing-request="false", l'objet est prêt (disponible temporairement)
            }

            // 2. Téléchargement direct vers le chemin local
            // L'option 'SaveAs' ouvre un flux vers le fichier sans saturer la RAM
            $this->client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SaveAs' => $localPath,
            ]);

            $result['current_status'] = 'telechargé';

            return $result;

        } catch (S3Exception $e) {
            var_dump($e->getMessage());
            return [
                'bucket' => $bucket,
                'key' => $key,
                'current_status' => 'erreur: ' . $e->getAwsErrorMessage()
            ];
        }
    }

    private function isFrozen(Result $headObject)
    {
        return $headObject->get('x-amz-restore'); //['x-amz-storage-class'] === 'GLACIER';
    }
}
