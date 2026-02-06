<?php

namespace App\CloudAccess;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;

class OldS3
{
    private S3ClientInterface $client;
    public function __construct(
        string $endpoint,
        string $region,
        string $accessKey,
        string $secretKey,
    )
    {
        $this->client = new S3Client(args: [
            'region'  => $region,
            'version' => 'latest',
            'endpoint' => $endpoint,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
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

    public function checkConnection(): bool
    {
        try {
            // Since bucket is dynamic, we check if we can list buckets to verify auth/connectivity
            $this->client->headObject(['Bucket' => 'sl-adullact-actes-2019', 'Key' => '212105340/002DU03122019/SLO-EACT--212105340--20191210-2.tar.gz']);
            echo "OldS3 Connection OK (headObject successful)." . PHP_EOL;
            return true;
        } catch (\Exception $e) {
            echo "OldS3 Connection Failed: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
}
