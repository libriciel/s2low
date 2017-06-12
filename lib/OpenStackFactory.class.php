<?php

use \OpenCloud\OpenStack;

class OpenStackFactory {

    private $openstack_authentication_url_v2;
    private $openstack_username;
    private $openstack_password;
    private $openstack_tenant;

    public function __construct(
        $openstack_authentication_url_v2,
        $openstack_username,
        $openstack_password,
        $openstack_tenant
    ){
        $this->openstack_authentication_url_v2 = $openstack_authentication_url_v2;
        $this->openstack_username = $openstack_username;
        $this->openstack_password = $openstack_password;
        $this->openstack_tenant = $openstack_tenant;
    }


    public function getInstance(){
        return new OpenStack(
            $this->openstack_authentication_url_v2,
            array(
                'username'=> $this->openstack_username,
                'password'=> $this->openstack_password,
                'tenantName'  => $this->openstack_tenant
            )
        );
    }
}