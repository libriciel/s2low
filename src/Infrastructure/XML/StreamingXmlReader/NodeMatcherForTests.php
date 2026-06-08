<?php

namespace S2low\Infrastructure\XML\StreamingXmlReader;

use XMLReader;

class NodeMatcherForTests implements NodeMatcher
{
    private array $matchedValues = [];

    public function onStartElement(XMLReader $xmlReader): void
    {
        $this->matchedValues[] = $xmlReader->localName;
    }

    public function getResult(): array
    {
        return $this->matchedValues;
    }

    public function hasPendingMatches(): bool
    {
        return true;
    }

    public function onEndElement(XMLReader $xmlReader): void
    {
    }
}
