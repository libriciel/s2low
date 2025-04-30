<?php

namespace S2low\Factory;

use S2lowLegacy\Lib\OpenStackConfig;

class OpenStackConfigFactory
{
    public function __construct(
        private readonly array $cloudProvidersConfig
    ) {
    }

    public function create(string $key): OpenStackConfig
    {
        $config = $this->cloudProvidersConfig[$key] ?? throw new \InvalidArgumentException(
            "La cle '$key' n'est pas définie"
        );

        return new OpenStackConfig(
            $config['url'],
            $config['username'],
            $config['password'],
            $config['tenant'],
            $config['region'],
            $config['container_prefix']
        );
    }
}
