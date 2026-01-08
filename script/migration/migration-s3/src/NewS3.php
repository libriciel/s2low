<?php

namespace App;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;

class NewS3
{
    private S3ClientInterface $client;

    /**
     * @param string $accessKey
     * @param string $secretKey
     */
    public function __construct(string $accessKey, string $secretKey)
    {
        $this->client = new S3Client([
            'region'  => 'eu-west-3',
            'version' => 'latest',
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
    }

    public function uploadToNewS3($bucket, $localPath, $key)
    {
        $body = $this->getBody($localPath);
        try {
            $result = $this->client->putObject([
                'Bucket'      => $bucket,
                'Key'         => $key,
                'Body'        => $body,
                // Optionnel : définit le type de fichier (ex: text/plain, application/json)
                'ContentType' => 'text/plain',
            ]);

            return [
                'success' => true,
                'url'     => $result['ObjectURL'],
                'info'    => "Upload réussi"
            ];

        } catch (AwsException $e) {
            return [
                'success' => false,
                'error'   => $e->getAwsErrorCode(),
                'info'    => $e->getAwsErrorMessage()
            ];
        }
    }

    private function getBody($localPath)
    {

    }
}
