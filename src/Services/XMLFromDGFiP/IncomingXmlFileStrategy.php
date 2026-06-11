<?php

namespace S2low\Services\XMLFromDGFiP;

use RuntimeException;
use S2low\DTO\PesEntrantHandlingResult;
use S2low\Infrastructure\FileMetadata;
use S2low\Infrastructure\XML\RootFinder\XMLRootFinder;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;
use S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes;
use SplFileObject;

class IncomingXmlFileStrategy implements IncomingFileHandlingStrategy
{
    public function __construct(
        private readonly Parser $parser,
        private readonly iterable $xmlFileTypeStrategy,
    ) {
    }

    public function handle(FileMetadata $fileMetadata, SplFileObject $fileObject): PesEntrantHandlingResult
    {
        $rootElementName = $fileMetadata->getXmlRoot();
        $pesDocumentType = PesDocumentType::fromOrUnknown($rootElementName);

        [$extractedValues,$errors ] = $this->parser->parsePesFile(
            $fileObject,
            $pesDocumentType->getKeysToExtract(),
            $pesDocumentType->getXsdPath(),
        );

        $pes = new ParsedPes(
            $pesDocumentType,
            $rootElementName,
            $extractedValues,
            $errors
        );

        foreach ($this->xmlFileTypeStrategy as $strategy) {
            if ($strategy->canHandle($pes)) {
                return $strategy->handle($fileObject, $pes);
            }
        }

        throw new RuntimeException(
            sprintf(
                <<<TXT
                No available strategy to handle PES document.
                Root=%s
                Extracted values=%s
                Errors=%s
                TXT,
                $rootElementName,
                implode(',', $extractedValues),
                implode(',', $pes->getXsdErrors())
            )
        );
    }


    public function canHandle(FileMetadata $fileMetadata): bool
    {
        return $fileMetadata->isXmlFile();
    }
}
