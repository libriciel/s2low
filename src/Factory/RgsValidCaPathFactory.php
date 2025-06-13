<?php

namespace S2low\Factory;

class RgsValidCaPathFactory
{
    public function __construct(
        //        private readonly bool $onlyUseValidcargs = false,
        //        private readonly string $pathToRgsValidCa,
        //        private readonly string $pathToRgsValidCargs
    ) {
    }

    public function create(): string
    {
        return '';
//        return $this->onlyUseValidcargs ? $this->pathToRgsValidCargs : $this->pathToRgsValidCa;
    }
}
