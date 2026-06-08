<?php

namespace S2low\Services\XMLFromDGFiP;

use S2low\Infrastructure\XML\StreamingXmlReader\DepthTracker;
use S2low\Infrastructure\XML\StreamingXmlReader\NodeMatcher;
use S2low\Infrastructure\XML\StreamingXmlReader\XMLPathTracker;
use XMLReader;

class PesNodeMatcher implements NodeMatcher
{
    private array $foundValues = [];
    private int $expectedValueCount;
    private array $valuesMatcherAtdepth = [];

    /**
     * @param XMLPathTracker[] $valuesMatchers
     * @param DepthTracker $depthTracker Ensures xml depth traversal consistency
     */
    public function __construct(
        array $valuesMatchers,
        private readonly DepthTracker $depthTracker
    ) {
        $this->expectedValueCount = count($valuesMatchers);
        $this->valuesMatcherAtdepth[0] = $valuesMatchers;
    }

    /**
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    public function onStartElement(XMLReader $xmlReader): void
    {
        $currentDepth = $xmlReader->depth;
        $this->depthTracker->enterElement($currentDepth);

        if (empty($this->valuesMatcherAtdepth[$currentDepth])) {
            return;
        }

        foreach ($this->valuesMatcherAtdepth[$currentDepth] as $key => $valueToFind) {
            $depthBeforeMatch = $valueToFind->getCurrentMatchingDepth();
            $valueToFind->enterElement($xmlReader->localName);
            $depthAfterMatch = $valueToFind->getCurrentMatchingDepth();
            if ($valueToFind->hasMatched()) {
                // Pes files are encoded in ISO-8859-1
                $this->foundValues[$key] = mb_convert_encoding(
                    $xmlReader->getAttribute('V'),
                    'UTF-8',
                    'ISO-8859-1'
                );
                unset($this->valuesMatcherAtdepth[$currentDepth][$key]);
            } elseif ($depthAfterMatch === $depthBeforeMatch + 1) {
                $this->valuesMatcherAtdepth[$currentDepth + 1][$key] = $valueToFind;
                unset($this->valuesMatcherAtdepth[$currentDepth][$key]);
            }
        }
    }

    public function getResult(): array
    {
        return $this->foundValues;
    }

    public function hasPendingMatches(): bool
    {
        return $this->expectedValueCount !== count($this->foundValues);
    }


    /**
     * @throws \S2low\Exceptions\XMLParsingDesynchronizationException
     */
    public function onEndElement(XMLReader $xmlReader): void
    {
        $currentDepth = $xmlReader->depth;

        $this->depthTracker->leaveElement($currentDepth);
        if (empty($this->valuesMatcherAtdepth[$currentDepth])) {
            return;
        }

        foreach ($this->valuesMatcherAtdepth[$currentDepth] as $key => $valueToFind) {
            $valueToFind->leaveElement($xmlReader->localName);
            if ($valueToFind->cannotBeMatched()) {
                unset($this->valuesMatcherAtdepth[$currentDepth][$key]);
            }
        }
    }
}
