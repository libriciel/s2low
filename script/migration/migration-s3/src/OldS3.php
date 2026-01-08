<?php

namespace App;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;

class OldS3
{
    private S3ClientInterface $client;
    public function __construct()
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


    function getFile(string $bucket, string $key): array
    {
        try {
            // 1. Test d'existence via les métadonnées (HEAD request)
            $this->client->headObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            // 2. Si on arrive ici, le fichier existe, on le télécharge
            $result = $this->client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            return [
                'success' => true,
                'path'    => $result['Body']->getContents(),
                'info'    => "Fichier récupéré avec succès"
            ];

        } catch (AwsException $e) {
            // Le code 404 signifie que le fichier n'existe pas
            if ($e->getStatusCode() === 404) {
                return [
                    'success' => false,
                    'error'   => 'not_found',
                    'info'    => "Le fichier n'existe pas sur le bucket."
                ];
            }

            // Autres erreurs (droits d'accès, réseau, etc.)
            return [
                'success' => false,
                'error'   => 'api_error',
                'info'    => $e->getAwsErrorMessage()
            ];
        }
    }
}
