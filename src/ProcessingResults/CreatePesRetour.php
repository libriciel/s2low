<?php

namespace S2low\ProcessingResults;

use S2low\Exceptions\PesEntrantSavingException;
use S2low\Infrastructure\Directory;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Model\HeliosRetourSQL;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

class CreatePesRetour
{
    public function __construct(
        #[Autowire(service: 'app.heliosResponseDirectory')]
        private readonly Directory $responseDirectory,
        private readonly HeliosRetourSQL $heliosRetourSQL,
        private readonly Database $database,
        private readonly Filesystem $filesystem
    ) {
    }

    public function execute(string $siret, int $authorityId, SplFileObject $fileObject): void
    {
        try {
            $this->database->begin();
            $this->heliosRetourSQL->add(
                $authorityId,
                $siret,
                $fileObject->getBasename(),
                filesize($fileObject->getPathname()),
                sha1_file($fileObject->getPathname())
            );

            $this->filesystem->rename(
                $fileObject->getPathname(),
                $this->responseDirectory->getPath($fileObject->getBasename())
            );
            $this->database->commit();
        } catch (Throwable $e) {
            $this->database->rollback();
            throw new PesEntrantSavingException(
                sprintf(
                    '[%s] %s',
                    get_class($e),
                    $e->getMessage()
                )
            );
        }
    }
}
