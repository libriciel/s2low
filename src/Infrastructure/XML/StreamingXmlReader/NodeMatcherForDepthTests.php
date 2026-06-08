<?php

namespace S2low\Infrastructure\XML\StreamingXmlReader;

use XMLReader;

class NodeMatcherForDepthTests implements NodeMatcher
{
    private array $nodes;
    public function __construct(private XMLPathTracker $xmlTracker)
    {
    }

    public function onStartElement(XMLReader $xmlReader): void
    {
        echo sprintf("%s [depth %s] : entering\n", $xmlReader->localName, $xmlReader->depth);
        $this->nodes[] = $xmlReader->localName;
        $this->xmlTracker->enterElement($xmlReader->localName, $xmlReader->depth);
    }

    public function getResult(): array
    {
        return $this->nodes;
    }

    public function hasPendingMatches(): bool
    {
        return true;
    }

    public function onEndElement(XMLReader $xmlReader): void
    {
        echo sprintf("%s [depth %s] : leaving\n", $xmlReader->localName, $xmlReader->depth);
        $this->xmlTracker->leaveElement($xmlReader->localName, $xmlReader->depth);
    }
}
