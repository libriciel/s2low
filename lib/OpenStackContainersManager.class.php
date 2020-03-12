<?php


class OpenStackContainersManager {

    const NUMBER_OF_ATTEMPTS = 1;

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
	public function addConfiguration(string $containerName,
                                     OpenStackConfig $openStackConfig){
		$this->containerWrappers[$containerName]=
            $this->openStackContainerWrapperFactory->getContainerWrapper($containerName,$openStackConfig);
	}

    /**
     * @param $containerName
     * @throws UnrecoverableException
     */

	private function checkContainerAvailability($containerName){
        if(!isset($this->containerWrappers[$containerName])){
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

	private function getContainerWrapper($containerName){
        $this->checkContainerAvailability($containerName);
        return $this->containerWrappers[$containerName];
    }

    /**
     * @param $containerName
     * @param $function
     * @param $options
     * @return bool|object|\OpenStack\ObjectStore\v1\Models\StorageObject|\Psr\Http\Message\StreamInterface|void
     * @throws UnrecoverableException
     */

    public function execute($containerName,$function,$options){
	    $this->checkContainerAvailability($containerName);
	    $attempts = 0;
	    do{
	        try{
	            return $this->getContainerWrapper($containerName)->execute($function,$options);
            } catch (Exception $e){
	            $attempts++;
                $this->containerWrappers[$containerName]->resetConnection();
                continue;
            }

            break;

        } while($attempts < self::NUMBER_OF_ATTEMPTS);
    }
}