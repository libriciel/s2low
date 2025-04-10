<?php

namespace S2lowLegacy\Class\helios;

interface IWorkspace
{
    public function getHeliosFilesUploadRoot();

    public function getRepertoirePesAllerSansTransaction();

    public function getHeliosResponsesRoot();

    public function getHeliosResponsesErrorPath();
}
