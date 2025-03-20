<?php

namespace S2low\Enum;

enum ModulePermission: string
{
    case Modification = 'RW';
    case Visualisation = 'RO';
    case Aucune = 'NONE';
}
