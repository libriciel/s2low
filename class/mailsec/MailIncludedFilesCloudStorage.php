<?php

namespace S2lowLegacy\Class\mailsec;

use Monolog\Logger;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class MailIncludedFilesCloudStorage extends CloudStorage
{
    public function __construct(
        MailIncludedFilesCloudStorable $iCloudStorable,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        Logger $logger,
        $openstack_enable
    ) {
        parent::__construct($iCloudStorable, $openStackSwiftWrapper, $logger, $openstack_enable);
    }
}
