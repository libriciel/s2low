<?php

namespace S2low\Infrastructure\XML\RootFinder;

use RuntimeException;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use XMLReader;

class XMLRootFinder
{
    public function getRootElementName(SplFileObject $fileObject): string
    {
        $reader = new XMLReader();
        $reader->open(
            $fileObject->getPathname(),
            null,
            LIBXML_NONET | LIBXML_COMPACT
        );
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                $root = $reader->localName;
                $reader->close();
                return $root;
            }
        }

        $reader->close();
        throw new RuntimeException('No root element found');
    }
}
