<?php

namespace S2low\Factory;

use S2lowLegacy\Class\actes\ActesEnvelopeStorage;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorage;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackContainerWrapperFactory;

class OpenStackContainerStoreFactory
{
    private const ACTES = 'actes';
    private const PES_ALLER = 'helios_aller';
    private const PES_ACQUIT = 'helios_acquit';
    private const PES_RETOUR = 'helios_retour';
    private const MAILSEC = 'mailsec';

    public function __construct(
        private readonly OpenStackConfigFactory $openStackConfigFactory,
        private readonly OpenStackContainerWrapperFactory $openStackContainerWrapperFactory
    ) {
    }

    public function create(): OpenStackContainerStore
    {
        $openStackContainerStore = new OpenStackContainerStore(
            $this->openStackContainerWrapperFactory
        );
        $openStackContainerStore->addConfiguration(
            ActesEnvelopeStorage::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::ACTES)
        );
        $openStackContainerStore->addConfiguration(
            PESAllerCloudStorage::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::PES_ALLER)
        );
        $openStackContainerStore->addConfiguration(
            PESAcquitCloudStorage::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::PES_ACQUIT)
        );
        $openStackContainerStore->addConfiguration(
            PESRetourCloudStorage::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::PES_RETOUR)
        );
        $openStackContainerStore->addConfiguration(
            MailIncludedFilesCloudStorage::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::MAILSEC)
        );
        return $openStackContainerStore;
    }
}
