<?php

namespace S2lowLegacy\Class\actes;

use Libriciel\LibActes\ArchiveValidator;

class ArchiveValidatorFactory
{
    public function get($id_tdt, $id_application = 'EACT', $is_mandatory_typologie = false): ArchiveValidator
    {
        return new ArchiveValidator($id_tdt, $id_application, $is_mandatory_typologie);
    }
}
