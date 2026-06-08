<?php

namespace S2low\Services\XMLFromDGFiP\PesBuilder;

use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;

class PesBuilder
{
    public function __construct()
    {
    }

    /**
     * @param string $rootElementName
     * @param \S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType $pesDocumentType
     * @param array $extractedValues
     * @param array $validationErrors
     * @return \S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes
     */
    public function createFromParsedValues(
        string $rootElementName,
        PesDocumentType $pesDocumentType,
        array $extractedValues,
        array $validationErrors
    ): ParsedPes {
            return new ParsedPes(
                $pesDocumentType,
                $rootElementName,
                $extractedValues,
                $validationErrors
            );
    }
}
