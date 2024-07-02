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
        private readonly ObjectInstancier $objectInstancier,
        private readonly bool $openstack_enable,
    ) {
    }

    /**
     * @param string $classname
     * @return CloudStorage
     * @throws UnrecoverableException
     */
    public function getInstanceByClassName(string $classname)
    {
        $icloudStorable = $this->objectInstancier->get($classname);
        if (! $icloudStorable instanceof ICloudStorable) {
            throw new UnrecoverableException(
                "Impossible de créer un CloudStorage à partir d'un objet $classname"
            );
        }
        return $this->getInstance($icloudStorable);
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
