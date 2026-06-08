<?php

namespace S2low\Infrastructure;

use Exception;
use finfo;
use S2low\Infrastructure\XML\RootFinder\XMLRootFinder;
use SplFileObject;

class FileMetadata
{
    private ?bool $isXml = null;
    private ?string $root = null;
    private ?string $mimeType = null;

    public function __construct(
        private readonly XMLRootFinder $xmlRootFinder,
        private readonly SplFileObject $file
    ) {
    }

    public function getExtension(): string
    {
        return $this->file->getExtension();
    }

    public function isXmlFile(): bool
    {
        if (!is_null($this->isXml)) {
            return $this->isXml;
        }
        $this->initXmlMetaData();
        return $this->isXml;
    }

    public function getXmlRoot(): ?string
    {
        if (!is_null($this->root)) {
            return $this->root;
        }
        if ($this->isXml === false) {
            return null;
        }
        $this->initXmlMetaData();
        return $this->root;
    }

    private function initXmlMetaData()
    {
        try {
            $root = $this->xmlRootFinder->getRootElementName($this->file);
            $this->root = $root;
            $this->isXml = true;
        } catch (Exception $exception) {
            $this->isXml = false;
        }
    }

    public function getMimeType(): string
    {
        if (!is_null($this->mimeType)) {
            return $this->mimeType;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->mimeType = $finfo->file($this->file->getPathname());
        return $this->mimeType;
    }
}
