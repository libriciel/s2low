<?php

namespace S2low\Services\XMLFromDGFiP;

use Psr\Log\LoggerInterface;
use S2low\Infrastructure\XML\StreamingXmlReader\DepthTracker;
use S2low\Infrastructure\XML\StreamingXmlReader\XmlNodeStreamParser;
use S2low\Infrastructure\XML\StreamingXmlReader\XMLPathTracker;
use SplFileObject;

class Parser
{
    public function __construct(
        private readonly LoggerInterface $s2lowLogger,
        private readonly XmlNodeStreamParser $xmlNodeStreamParser
    ) {
    }

    /**
     * @param \SplFileObject $fileObject
     * @param array $expectedValuesInXml <string, array<string>>
     * @param string|null $xsdPathname
     * @return array
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    public function parsePesFile(SplFileObject $fileObject, array $expectedValuesInXml, ?string $xsdPathname): array
    {
        $valuesToFind = [];
        foreach ($expectedValuesInXml as $key => $path) {
            $valuesToFind[$key] = new XMLPathTracker($path);
        }
        $matcher = new PesNodeMatcher($valuesToFind, new DepthTracker());

        [$extractedValues,$errors ] = $this->xmlNodeStreamParser->parse(
            $fileObject->getPathname(),
            $matcher,
            $xsdPathname
        );

        if ($errors) {
            $this->s2lowLogger->error('Erreur lors de la validation du schéma XML');
            $this->s2lowLogger->error(json_encode($errors));
        }

        return [$extractedValues,$errors ];
    }
}
