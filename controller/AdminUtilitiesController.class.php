<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\Module;
use S2lowLegacy\Model\GroupSQL;

class AdminUtilitiesController extends Controller
{
    public function indexAction()
    {
        $this->verifSuperAdmin();
        $this->{'module_list'} = Module::getActiveModulesIdName();
        $this->{'group_list'} = $this->getObjectInstancier()->get(GroupSQL::class)->getAll();
    }
}
