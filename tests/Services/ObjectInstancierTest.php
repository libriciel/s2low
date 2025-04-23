<?php

namespace S2low\Tests\Services;

use S2low\Tests\S2lowSymfonyWebTestCase;
use S2lowLegacy\Class\actes\ActesEnvelopeStorage;
use S2lowLegacy\Class\actes\ActesPdfLegacy;
use S2lowLegacy\Class\helios\PESAcquitCloudStorage;
use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorage;
use S2lowLegacy\Lib\OpenStackContainerFetcher;
use S2lowLegacy\Lib\OpenStackContainerWrapper;
use S2lowLegacy\Lib\OpenStackContainerWrapperFactory;
use S2lowLegacy\Lib\Recuperateur;

class ObjectInstancierTest extends S2lowSymfonyWebTestCase
{
    private const NOT_INSTANCIABLE_CLASSES =
        [
            Recuperateur::class,                        #Doit être explicitement instancié
            OpenStackContainerWrapperFactory::class,    # Les classes suivantes doivent être explicitement instanciées,
            ActesEnvelopeStorage::class,                # non set dans l'objectInstancier
            OpenStackContainerFetcher::class,
            OpenStackContainerWrapper::class,
            PESAcquitCloudStorage::class,
            PESRetourCloudStorage::class,
            MailIncludedFilesCloudStorage::class,
            ActesPdfLegacy::class
        ];
    public function testAllClassesCanBeInstantiated()
    {
        self::expectNotToPerformAssertions();
        foreach (get_declared_classes() as $class) {
            if (
                preg_match('/^S2lowLegacy/', $class)
                &&
                !in_array($class, self::NOT_INSTANCIABLE_CLASSES)
            ) {
                LegacyObjectsManager::getLegacyObjectInstancier()->get($class);
            }
        }
    }
}
