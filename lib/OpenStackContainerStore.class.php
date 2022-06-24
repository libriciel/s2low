<?php

class OpenStackContainerStore
{
    /** @var OpenStackContainerWrapper[] */
    private $containerWrappers;
    private $openStackContainerWrapperFactory;

    public function __construct(OpenStackContainerWrapperFactory $openStackContainerWrapperFactory)
    {
        $this->openStackContainerWrapperFactory = $openStackContainerWrapperFactory;
    }

    /**
     * @param string $containerName
     * @param OpenStackConfig $openStackConfig
     */
    public function addConfiguration(
        string $containerName,
        OpenStackConfig $openStackConfig
    ) {
        $this->containerWrappers[$containerName] =
            $this->openStackContainerWrapperFactory->getContainerWrapper($containerName, $openStackConfig);
    }

    /**
     * @param $containerName
     * @throws UnrecoverableException
     */

    private function checkContainerAvailability($containerName)
    {
        if (!isset($this->containerWrappers[$containerName])) {
            throw new UnrecoverableException(
                "Impossible de trouver la configuration Openstack pour $containerName"
            );
        }
    }
    /**
     * @param $containerName
     * @return OpenStackContainerWrapper
     * @throws UnrecoverableException
     */

    public function getContainerWrapper($containerName)
    {
        $this->checkContainerAvailability($containerName);
        return $this->containerWrappers[$containerName];
    }
}
