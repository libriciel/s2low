<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\UnrecoverableException;
use Monolog\Logger;

class CloudStorageFactory
{
    private $openStackSwiftWrapper;
    private $logger;

    private $objectInstancier;

    public function __construct(
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        Logger $logger,
        ObjectInstancier $objectInstancier
    ) {
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
        $this->logger = $logger;
        $this->objectInstancier = $objectInstancier;
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
            $this->logger
        );
    }
}
