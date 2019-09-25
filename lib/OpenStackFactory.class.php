<?php

use \OpenCloud\OpenStack;

class OpenStackFactory {

	private $openStackConfig;

	/**
	 * @param string $configuration_id
	 * @param OpenStackConfig $openStackConfig
	 */
	public function addConfiguration(string $configuration_id,OpenStackConfig $openStackConfig){
		$this->openStackConfig[$configuration_id] = $openStackConfig;
	}

	/**
	 * @param string $configuration_id
	 * @return OpenStack
	 * @throws UnrecoverableException
	 */
    public function getInstance(string $configuration_id){

		$openStackConfiguration = $this->getOpenStackConfiguration($configuration_id);
        return new OpenStack(
			$openStackConfiguration->openstack_authentication_url_v2,
            array(
                'username'=> $openStackConfiguration->openstack_username,
                'password'=> $openStackConfiguration->openstack_password,
                'tenantName'  => $openStackConfiguration->openstack_tenant
            )
        );
    }

	/**
	 * @param string $configuration_id
	 * @return OpenStackConfig
	 * @throws UnrecoverableException
	 */
    private function getOpenStackConfiguration(string $configuration_id) : OpenStackConfig {
		if (empty($this->openStackConfig[$configuration_id])){
			throw new UnrecoverableException(
				"Impossible de trouver la configuration Openstack pour $configuration_id"
			);
		}
		return $this->openStackConfig[$configuration_id];
	}

	/**
	 * @param string $configuration_id
	 * @return mixed
	 * @throws UnrecoverableException
	 */
    public function getOpenStackRegion(string $configuration_id)  {
    	return $this->getOpenStackConfiguration($configuration_id)->openstack_region;
	}

	/**
	 * @param string $configuration_id
	 * @return mixed
	 * @throws UnrecoverableException
	 */
	public function getOpenStackSwiftPrefix(string $configuration_id) {
		return $this->getOpenStackConfiguration($configuration_id)->openstack_swift_container_prefix;
	}


}