<?php

namespace S2lowLegacy\Lib;

class OpenStackConfig
{
    public function __construct(
        public readonly string $openstack_authentication_url_v3,
        public readonly string $openstack_username,
        public readonly string $openstack_password,
        public readonly string $openstack_tenant,
        public readonly string $openstack_region,
        public readonly string $openstack_swift_container_prefix
    ) {
    }
}
