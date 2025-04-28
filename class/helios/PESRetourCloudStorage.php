<?php

namespace S2lowLegacy\Class\helios;

use Monolog\Logger;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class PESRetourCloudStorage extends CloudStorage
{
    public function __construct(
        PESRetourCloudStorable $iCloudStorable,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        Logger $logger,
        $openstack_enable
    ) {
        parent::__construct($iCloudStorable, $openStackSwiftWrapper, $logger, $openstack_enable);
    }
}
