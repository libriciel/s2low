<?php

namespace S2low\Infrastructure\Adapter;

use OpenStack\OpenStack;
use S2low\Domain\Exception\CloudStorageDownloadException;
use S2low\Domain\Exception\CreateNewFileException;

class OpenStackAdapter
{
    private OpenStack $openstack;
    private \OpenStack\ObjectStore\v1\Models\Container $container;

    public function __construct(
        string                           $authUrl,
        string                           $region,
        string                           $username,
        string                           $password,
    )
    {
        $this->openstack = new OpenStack([
            'authUrl' => $authUrl,
            'region' => $region,
            'user' => [
                'name' => $username,
                'password' => $password,
                'domain' => ['name' => 'default']
            ]
        ]);
    }

    public function config($containerName): void
    {
        $service = $this->openstack->objectStoreV1();
        $this->container = $service->getContainer($containerName);
    }

    /**
     * @throws CreateNewFileException
     * @throws CloudStorageDownloadException
     */
    public function download($remoteFilePath, $localPathDestination): void
    {
        $object = $this->container->getObject($remoteFilePath);

        $fileStream = fopen($localPathDestination, 'w');

        if (!$fileStream) {
            throw new CreateNewFileException($localPathDestination);
        }

        try {
            $object->download(['stream' => $fileStream]);
        } catch (\Exception $e) {
            throw new CloudStorageDownloadException($localPathDestination, $e->getMessage());
        }

        fclose($fileStream);
    }
}