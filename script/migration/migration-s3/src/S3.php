<?php

namespace App;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class OldS3
{
    private string $accessKey;
    private string $secretKey;

    /**
     * @param string $accessKey
     * @param string $secretKey
     */
    public function __construct(string $accessKey, string $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }


    function getFile(string $bucket, string $key): array
    {
        $s3 = new S3Client([
            'region'  => 'eu-west-3',
            'version' => 'latest',
            'credentials' => [
                'key'    => $this->accessKey,
                'secret' => $this->secretKey,
            ],
        ]);

        try {
            // 1. Test d'existence via les métadonnées (HEAD request)
            $s3->headObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            // 2. Si on arrive ici, le fichier existe, on le télécharge
            $result = $s3->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);

            return [
                'success' => true,
                'data'    => $result['Body']->getContents(),
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
