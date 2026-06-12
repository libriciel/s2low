<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\Module;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Lib\ObjectInstancier;

class AdminUtilitiesController extends Controller
{
    public function __construct(
        private readonly ModuleSQL $moduleSQL,
        ObjectInstancier $objectInstancier
    ) {
        parent::__construct($objectInstancier);
    }
    public function indexAction()
    {
        $this->verifSuperAdmin();
        $this->{'module_list'} = $this->moduleSQL->getActiveModulesIdName();
        $this->{'group_list'} = $this->getObjectInstancier()->get(GroupSQL::class)->getAll();
    }
}
