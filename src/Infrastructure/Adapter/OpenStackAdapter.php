<?php

namespace S2low\Infrastructure\Adapter;

use OpenStack\OpenStack;

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

    public function download($remoteFilePath, $localPathDestination): void
    {
        $object = $this->container->getObject($remoteFilePath);

        $fileStream = fopen($localPathDestination, 'w');

        if (!$fileStream) {
            throw new \RuntimeException("Impossible d'écrire dans le fichier local : $localPathDestination");
        }

        $object->download(['stream' => $fileStream]);

        fclose($fileStream);
    }
}