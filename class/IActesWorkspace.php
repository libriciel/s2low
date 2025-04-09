<?php

namespace S2lowLegacy\Class;

interface IActesWorkspace
{
    public function getRepertoireActesEnveloppeSansTransaction(): string;

    public function getActesFilesUploadRoot(): string;

    public function getActesResponseTmpLocalPath(): string;

    public function getActesResponseErrorPath(): string;
}
