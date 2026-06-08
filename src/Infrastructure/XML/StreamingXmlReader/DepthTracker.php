<?php

namespace S2low\Infrastructure\XML\StreamingXmlReader;

use S2low\Exceptions\XMLParsingDesynchronizationException;

class DepthTracker
{
    private $xmlTraversalDepth = 0;

    public function enterElement(int $elementsDepthInXml): void
    {
        if ($elementsDepthInXml !== $this->xmlTraversalDepth) {
            throw new XMLParsingDesynchronizationException(
                sprintf(
                    'Invalid XML depth transition: expected %d, got %d',
                    $this->xmlTraversalDepth,
                    $elementsDepthInXml
                )
            );
        }
        $this->xmlTraversalDepth++;  // Increment current depth after processing this element
    }
    public function leaveElement(int $elementsDepthInXml): void
    {
        if ($this->xmlTraversalDepth === 0) {
            throw new XMLParsingDesynchronizationException('No element to leave');
        }

        if ($elementsDepthInXml !== $this->xmlTraversalDepth - 1) {
            throw new XMLParsingDesynchronizationException(
                sprintf(
                    'Invalid XML depth transition on leaving with depth %s',
                    $elementsDepthInXml,
                )
            );
        }

        $this->xmlTraversalDepth--;
    }
}
