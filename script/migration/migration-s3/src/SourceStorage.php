<?php

namespace App;

use Aws\Exception\AwsException;

class SourceStorage
{
    private OldS3 $oldS3Client;
    private $openStackContainer = null;

    public function __construct(array $config)
    {
        $this->oldS3Client = $this->createOldS3Client($config);

        if (!empty($config['OS_AUTH_URL'])) {
            $this->openStackContainer = $this->createOpenStackContainer($config);
        }
    }

    protected function createOldS3Client(array $config): OldS3
    {
        $s3Config = [
            'region'  => $config['OLD_S3_REGION'],
            'version' => 'latest',
            'endpoint' => $config['OLD_S3_ENDPOINT'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $config['OLD_S3_ACCESS_KEY'],
                'secret' => $config['OLD_S3_SECRET_KEY'],
            ],
        ];

        if (isset($config['handler'])) {
            $s3Config['handler'] = $config['handler'];
        }

        return new OldS3($s3Config);
    }

    protected function createOpenStackContainer(array $config)
    {
        $openstack = new \OpenStack\OpenStack([
            'authUrl' => $config['OS_AUTH_URL'],
            'region'  => $config['OS_REGION'],
            'user'    => [
                'name'     => $config['OS_USERNAME'],
                'password' => $config['OS_PASSWORD'],
                'domain'   => ['name' => 'Default'],
            ],
            'scope'   => ['project' => ['id' => $config['OS_PROJECT_ID']]]
        ]);

        $containerName = $config['OS_CONTAINER'];
        return $openstack->objectStoreV1()->getContainer($containerName);
    }

    public function getBucket(string $type, string $filename): string
    {
        return '';
    }

    public function downloadFile(string $key, bool $isOldS3First, string $destinationPath, string $type): bool
    {
        if ($isOldS3First) {
            if ($this->downloadFromOldS3($key, $destinationPath, $type)) {
                return true;
            }
            if ($this->downloadFromOpenStack($key, $destinationPath)) {
                return true;
            }
        } else {
            if ($this->downloadFromOpenStack($key, $destinationPath)) {
                return true;
            }
            if ($this->downloadFromOldS3($key, $destinationPath, $type)) {
                return true;
            }
        }
        return false;
    }

    private function downloadFromOldS3(string $key, string $destinationPath, string $type): bool
    {
        $bucket = $this->getBucket($type, $key);
        try {
            $this->oldS3Client->getObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SaveAs' => $destinationPath
            ]);
            return true;
        } catch (AwsException $e) {
            // echo "OldS3 Miss: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }

    private function downloadFromOpenStack(string $key, string $destinationPath): bool
    {
        if (!$this->openStackContainer) {
            return false;
        }
        try {
             $object = $this->openStackContainer->getObject($key);
             $stream = $object->download();
             file_put_contents($destinationPath, $stream);
             return true;
        } catch (\Exception $e) {
             // echo "OpenStack Miss: " . $e->getMessage() . PHP_EOL;
             return false;
        }
    }

    public function exists(string $key, string $type): string|bool
    {
        // Check OldS3
        $bucket = $this->getBucket($type, $key);
        try {
            $this->oldS3Client->headObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);
            return 'OldS3';
        } catch (\Exception $e) {
        }

        // Check OpenStack
        if ($this->openStackContainer) {
            if ($this->openStackContainer->objectExists($key)) {
                return 'OpenStack';
            }
        }

        return false;
    }

    public function checkConnection(): bool
    {
        $ok = true;
        // Check OldS3
        try {
//            $this->oldS3Client->listBuckets();

            echo "OldS3 Connection OK (ListBuckets successful)." . PHP_EOL;
        } catch (\Exception $e) {
             echo "OldS3 Connection Failed: " . $e->getMessage() . PHP_EOL;
             $ok = false;
        }

        // Check OpenStack
        if ($this->openStackContainer) {
            try {
                $this->openStackContainer->retrieve();
                echo "OpenStack Connection OK (Container accessible)." . PHP_EOL;
            } catch (\Exception $e) {
                 echo "OpenStack Connection Failed: " . $e->getMessage() . PHP_EOL;
                 $ok = false;
            }
        }

        return $ok;
    }

    public function test($bucket, $key, $localPath)
    {
        return $this->oldS3Client->getFile($bucket, $key, $localPath);
    }
}
