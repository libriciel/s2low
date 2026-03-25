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
            'http'    => [
                'connect_timeout' => 10,
                'timeout'         => 3600,
            ],
        ]);
    }

    public function getS3Client(): S3ClientInterface
    {
        return $this->client;
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
            $contentLength = $meta['ContentLength'] ?? 0;

            $result = [
                'bucket' => $bucket,
                'key' => $key,
                'local_path' => $localPath,
                'current_status' => 'autre',
                'size' => $contentLength
            ];

            // ... (rest of the logic remains same, just adding more logs)
            $isArchived = in_array($storageClass, ['GLACIER', 'DEEP_ARCHIVE']);

            if ($isArchived) {
                // ... (existing logic)
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

                if (str_contains($restoreHeader, 'ongoing-request="true"')) {
                    $result['current_status'] = 'en attente de restoration';
                    return $result;
                }
            }

            // 2. Téléchargement direct vers le chemin local
            $this->client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SaveAs' => $localPath,
            ]);

            $result['current_status'] = 'telechargé';

            return $result;

        } catch (S3Exception $e) {
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

    public function exists(string $bucket, string $key): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $key
            ]);
            return true;
        } catch (S3Exception $e) {
            $code = $e->getAwsErrorCode() ?: $e->getStatusCode();
            // Si c'est un vrai NotFound (404), on renvoie calmement false sans bruit
            if (in_array($code, ['NotFound', 'NoSuchKey', 'NoSuchBucket', '404', 404])) {
                return false;
            }
            // Sinon, c'est probablement une erreur de droits (403), de réseau, ou autre chose qui crée un faux négatif
            echo "\n[OldS3->exists] ATTENTION: Erreur inattendue pour {$bucket}/{$key} : " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }

    public function unfreeze()
    {

    }

    public function listBuckets(): array
    {
        try {
            $result = $this->client->listBuckets();
            $buckets = [];
            foreach ($result['Buckets'] as $bucket) {
                $buckets[] = $bucket['Name'];
            }
            return $buckets;
        } catch (\Exception $e) {
            echo "Failed to list buckets: " . $e->getMessage() . PHP_EOL;
            return [];
        }
    }
}
