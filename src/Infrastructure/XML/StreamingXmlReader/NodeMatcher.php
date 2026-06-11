<?php

namespace S2low\Infrastructure\XML\StreamingXmlReader;

use XMLReader;

interface NodeMatcher
{
    /**
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    public function onStartElement(XMLReader $xmlReader): void;
    public function getResult(): array;
    public function hasPendingMatches(): bool;
    /**
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    public function onEndElement(XMLReader $xmlReader): void;
}
