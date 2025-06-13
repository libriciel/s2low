<?php

namespace S2lowLegacy\Lib;

use OpenStack\OpenStack;
use Psr\Log\LoggerInterface;

class OpenStackContainerWrapperFactory
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getContainerWrapper(string $containerName, OpenStackConfig $configuration)
    {
        $containerFullName = $configuration->openstack_swift_container_prefix . $containerName;

        $parametres = [
            "authUrl" => $configuration->openstack_authentication_url_v3,
            "region" => $configuration->openstack_region,
            "user" => [
                'name' => $configuration->openstack_username,
                'password' => $configuration->openstack_password,
                'domain' => ['name' => 'Default']
            ]
        ];

        $openStack = new OpenStack($parametres);

        $openStackContainerFetcher = new OpenStackContainerFetcher($containerFullName, $parametres, $openStack, $this->logger);

        return new OpenStackContainerWrapper(
            $openStackContainerFetcher,
            $this->logger,
            new OpenStackStateManager(
                $this->logger
            )
        );
    }
}
