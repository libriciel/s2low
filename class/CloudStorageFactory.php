<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\UnrecoverableException;
use Monolog\Logger;

class CloudStorageFactory
{
    public function __construct(
        private readonly OpenStackSwiftWrapper $openStackSwiftWrapper,
        private readonly Logger $logger,
        private readonly bool $openstack_enable,
    ) {
    }

    public function getInstance(ICloudStorable $ICloudStorable)
    {
        return new CloudStorage(
            $ICloudStorable,
            $this->openStackSwiftWrapper,
            $this->logger,
            $this->openstack_enable
        );
    }
}
