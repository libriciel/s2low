<?php

namespace S2low\Services\XMLFromDGFiP\PesBuilder;

use LibXMLError;
use S2low\Exceptions\MissingFieldInParsedPesException;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;

class ParsedPes
{
    /**
     * @param \S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType $pesDocumentType
     * @param string $rootName
     * @param array $content
     * @param LibXMLError[] $xsdErrors
     */
    public function __construct(
        private readonly PesDocumentType $pesDocumentType,
        private readonly string $rootName,
        private readonly array $content,
        private readonly array $xsdErrors
    ) {
    }

    /**
     * @return string
     */
    public function getRootName(): string
    {
        return $this->rootName;
    }

    public function hasXsdErrors(): bool
    {
        return  !empty($this->xsdErrors);
    }

    public function getXsdErrors(): array
    {
        return array_map(
            static fn(\LibXMLError $error) => sprintf(
                '[line %d] %s',
                $error->line,
                trim($error->message),
            ),
            $this->xsdErrors
        );
    }

    public function hasType(PesDocumentType $pesDocumentType): bool
    {
        return $this->pesDocumentType === $pesDocumentType;
    }

    /**
     * @throws \S2low\Exceptions\MissingFieldInParsedPesException
     */
    public function getStringValue(string $key, bool $allowNull = false): ?string
    {
        if (!isset($this->content[$key])) {
            if ($allowNull) {
                return null;
            }
            throw new MissingFieldInParsedPesException(
                sprintf(
                    '[ %s] Champ %s introuvable. Champs disponibles : %s',
                    $this->pesDocumentType->value,
                    $key,
                    implode(', ', array_keys($this->content))
                )
            );
        }
        return (string) $this->content[$key];
    }

    public function hasValue(string $key): bool
    {
        return isset($this->content[$key]);
    }
}
