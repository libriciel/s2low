<?php

namespace S2low\Infrastructure\XML\StreamingXmlReader;

use RuntimeException;
use XMLReader;

/**
 * Streaming XML parser based on XMLReader.
 */
class XmlNodeStreamParser
{
    public function __construct(
        private readonly string $schema_pes_path,
    ) {
    }

    /**
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    public function parse(string $filename, NodeMatcher $nodeMatcher, ?string $xsdSchemaRelativePath = null): array
    {
        $schemaPath = null;
        $previousLibxmlState = null;

        if (!is_null($xsdSchemaRelativePath)) {
            $schemaPath = $this->schema_pes_path . $xsdSchemaRelativePath;
            $previousLibxmlState = libxml_use_internal_errors(true);
        }

        try {
            $XMLReader = XMLReader::open($filename);

            if (!$XMLReader) {
                throw new RuntimeException('Unable to open file "' . $filename . '"');
            }

            $mustValidateXSD = false;
            if (! is_null($schemaPath)) {
                $mustValidateXSD = true;
                $XMLReader->setSchema($schemaPath);
            }

            $this->consumeXmlAndMatchNodes($nodeMatcher, $XMLReader);
            if ($mustValidateXSD) {
                $this->consumeXmlUntilEof($XMLReader);
            }
        } finally {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (isset($XMLReader) && $XMLReader instanceof XMLReader) {
                $XMLReader->close();
            }
            if (!is_null($previousLibxmlState)) {
                libxml_use_internal_errors($previousLibxmlState);
            }
        }
        return [$nodeMatcher->getResult(), $errors];
    }

    /**
     * Consumes the XML stream and matches until no more match is needed
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    private function consumeXmlAndMatchNodes(NodeMatcher $nodeMatcher, XMLReader $XMLReader): void
    {
        while ($nodeMatcher->hasPendingMatches() && $XMLReader->read()) {
            if ($XMLReader->nodeType === XMLReader::ELEMENT) {
                $nodeMatcher->onStartElement($XMLReader);
                if ($XMLReader->isEmptyElement) {
                    $nodeMatcher->onEndElement($XMLReader);
                }
            }
            if ($XMLReader->nodeType === XMLReader::END_ELEMENT) {
                $nodeMatcher->onEndElement($XMLReader);
            }
        }
    }

    /**
     * Fully consumes the XML stream so that libxml XSD validation can complete.
     * No matching or extraction is performed.
     */
    private function consumeXmlUntilEof(XMLReader $XMLReader): void
    {
        while ($XMLReader->read()) {
            // Continue to validate the XML without trying to match values
        }
    }
}
