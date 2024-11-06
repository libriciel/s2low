<?php

namespace S2lowLegacy\Class;

interface ICloudStorableWithLoseFiles extends ICloudStorable
{
    public function getPathRelativeToUploadDir(string $dirtemp): string;
}
