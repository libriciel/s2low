<?php

use \OpenCloud\OpenStack;

class OpenStackFactory {

    private $openStackConfig;

    public function __construct(
		OpenStackConfig $openStackConfig
    ){
        $this->openStackConfig = $openStackConfig;
    }

	/**
	 * @return OpenStack
	 */
    public function getInstance(){
        return new OpenStack(
			$this->openStackConfig->openstack_authentication_url_v2,
            array(
                'username'=> $this->openStackConfig->openstack_username,
                'password'=> $this->openStackConfig->openstack_password,
                'tenantName'  => $this->openStackConfig->openstack_tenant
            )
        );
    }




}