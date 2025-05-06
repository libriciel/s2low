<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class PESAllerCloudStorage extends CloudStorage
{
    public function __construct(
        PESAllerCloudStorable $iCloudStorable,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        LoggerInterface $logger,
        $openstack_enable
    ) {
        parent::__construct($iCloudStorable, $openStackSwiftWrapper, $logger, $openstack_enable);
    }
}
