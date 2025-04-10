<?php

namespace S2lowLegacy\Class;

interface IActesWorkspace
{
    public function getRepertoireEnveloppeSansTransaction(): string;

    public function getFilesUploadRoot(): string;

    public function getResponseTmpLocalPath(): string;

    public function getResponseErrorPath(): string;
}
