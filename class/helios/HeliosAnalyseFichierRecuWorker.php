<?php

namespace S2lowLegacy\Class\helios;

use RuntimeException;
use S2low\Infrastructure\Directory;
use S2low\Services\FilesAndDirectoriesUtils\DirectoryScanner;
use S2lowLegacy\Class\IWorker;
use Exception;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HeliosAnalyseFichierRecuWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-analyse-fichier-recu';

    public function __construct(
        private readonly IncomingFileProcessor $incomingFileProcessor,
        #[Autowire(service: 'app.heliosFilesIncoming')]
        private readonly Directory $incoming,
        private readonly DirectoryScanner $scanner,
    ) {
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getData($id): mixed
    {
        return $id;
    }

    /**
     * @return array
     */
    public function getAllId(): array
    {
        return $this->scanner->getFileNames($this->incoming);
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $filePath = $this->incoming->getPath($data);
        if (!is_file($filePath)) {
            throw new RuntimeException("Fichier introuvable : $filePath");
        }
        $file = new SplFileObject(realpath($filePath));
        $this->incomingFileProcessor->process($file);
    }

    public function getMutexName($data): string
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    public function isDataValid($data): bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function start(): void
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
