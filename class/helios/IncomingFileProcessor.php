<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use RuntimeException;
use S2low\DTO\PesEntrantHandlingResult;
use S2low\Enum\IncomingFileHandlingStatus;
use S2low\Exceptions\PesEntrantSavingException;
use S2low\Infrastructure\FileMetadataFactory;
use S2low\ProcessingResults\MoveFileToErrorDirectoryFactory;
use SplFileObject;
use Throwable;

final class IncomingFileProcessor
{
    public function __construct(
        private readonly LoggerInterface $s2lowLogger,
        private readonly FileMetadataFactory $fileInspectorFactory,
        private readonly MoveFileToErrorDirectoryFactory $makeMoveFileToErrorDirectoryFactory,
        private readonly iterable $fileHandlingStrategies,
    ) {
    }

    /**
     * @param \SplFileObject $fileObject
     * @return \S2low\DTO\PesEntrantHandlingResult
     * @throws \S2low\Exceptions\MoveFileException
     */
    public function process(
        SplFileObject $fileObject
    ): PesEntrantHandlingResult {
        $this->s2lowLogger->info("Traitement de {$fileObject->getPathname()}");
        $this->s2lowLogger->debug("Taille du fichier {$fileObject->getPathname()} en octets : {$fileObject->getSize()}");

        $fileInspector = $this->fileInspectorFactory->create($fileObject);

        try {
            foreach ($this->fileHandlingStrategies as $fileHandlingStrategy) {
                /** @var \S2low\Services\XMLFromDGFiP\IncomingFileHandlingStrategy $fileHandlingStrategy */
                if ($fileHandlingStrategy->canHandle($fileInspector)) {
                    return $fileHandlingStrategy->handle($fileInspector, $fileObject);
                }
            }
            throw new RuntimeException(
                sprintf(
                    'Aucun handler compatible pour "%s" (type MIME: %s).',
                    $fileObject->getPathname(),
                    $fileInspector->getMimeType(),
                )
            );
        } catch (PesEntrantSavingException $e) {
            /**
             * This error occurs if the file can't be moved or the transaction can't be created in the database.
             * We let the file where it already is, it will be handled next time.
             */
            $this->s2lowLogger->error(
                'Échec du traitement du fichier',
                [
                    'file' => $fileObject->getPathname(),
                    'exception' => $e
                ]
            );
            return new PesEntrantHandlingResult(IncomingFileHandlingStatus::SavingFailed, $e->getMessage());
        } catch (Throwable $e) {
            $this->makeMoveFileToErrorDirectoryFactory->get()->execute($fileObject, $e->getMessage());
            $this->s2lowLogger->error(
                'Échec du traitement du fichier',
                [
                    'file' => $fileObject->getPathname(),
                    'exception' => $e
                ]
            );
            return new PesEntrantHandlingResult(IncomingFileHandlingStatus::AnalysisFailed, $e->getMessage());
        }
    }
}
