<?php

namespace S2low\Infrastructure\XML\StreamingXmlReader;

use InvalidArgumentException;
use LogicException;

final class XMLPathTracker
{
    public const JOKER_CHARACTER = '*';
    private int $currentMatchingDepth = 0;
    private bool $cannotBeMatched = false;
    private int $pathLength;
    private bool $hasMatched = false;

    /**
     * @param string[] $path Array of XML element names representing the path to match.
     *                       Each name is assumed to be unique in the XML document.
     */
    public function __construct(private array $path)
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty.');
        }
        $this->pathLength = count($path);
    }

    public function enterElement(string $elementName): void
    {
        if ($this->hasMatched || $this->cannotBeMatched) {
            return;
        }
        if ($this->getCurrentElementToMatch() === self::JOKER_CHARACTER) {
            // The Joker character matches whatever element comes first
            // But if there is a leave afterwards, the Path cannot be matched
            $this->path[$this->currentMatchingDepth] = $elementName;
        }
        if ($this->getCurrentElementToMatch() === $elementName) {
            // Extend current matching depth if this element continues the expected path
            $this->currentMatchingDepth++;
        }
        if ($this->currentMatchingDepth === $this->pathLength) {
            $this->hasMatched = true;
        }
    }

    public function leaveElement(string $elementName): void
    {
        if ($this->hasMatched || $this->cannotBeMatched) {
            return;
        }
        if ($this->currentMatchingDepth === 0) {
            throw new LogicException("Cannot leave $elementName  when no elements have been matched.");
        }
        //
        if ($this->getLastMatchedElementName() === $elementName) {
            if ($this->currentMatchingDepth < $this->pathLength) {
                // Leaving a partially matched element makes a full path match impossible
                $this->cannotBeMatched = true;
            }
            $this->currentMatchingDepth--;
        }
    }

    public function hasMatched(): bool
    {
        return $this->hasMatched;
    }

    public function cannotBeMatched(): bool
    {
        return $this->cannotBeMatched;
    }

    public function getCurrentMatchingDepth(): int
    {
        return $this->currentMatchingDepth;
    }

    /**
     * @return int
     */
    private function getLastMatchedIndex(): int
    {
        return $this->currentMatchingDepth - 1;
    }

    /**
     * @return string
     */
    private function getLastMatchedElementName(): ?string
    {
        return $this->path[$this->getLastMatchedIndex()] ?? null;
    }

    /**
     * @return string
     */
    private function getCurrentElementToMatch(): string
    {
        return $this->path[$this->currentMatchingDepth];
    }
}
