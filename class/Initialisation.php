<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

class Initialisation
{
    public const MODULENAMEACTES = 'actes';
    public const DROITSACTES = ['CS','TT'];
    public const MODULENAMEHELIOS = 'helios';
    public const MODULENAMEMAIL = 'mail';
    private UserSQL $userSQL;
    private ModuleSQL $moduleSQL;
    private AuthoritySQL $authoritySQL;
    private GroupSQL $groupSQL;

    public function __construct(
        protected ObjectInstancier $objectInstancier,
        protected string $html,
        protected JSONoutput $jsonOutput,
        protected SQLQuery $sqlQuery,
        protected FrontController $frontController,
        protected Droit $droit,
        protected S2lowRedirect $s2lowRedirect
    ) {
        $this->moduleSQL = new ModuleSQL($this->sqlQuery);
        $this->userSQL = new UserSQL($this->sqlQuery);
        $this->authoritySQL = new AuthoritySQL($this->sqlQuery);
        $this->groupSQL = new GroupSQL($this->sqlQuery);
    }

    /**
     * @throws \Exception
     */
    public function doInit(): InitData
    {
        $connexion = new Connexion();
        $me = null;
        if (!$connexion->isConnected()) {
            $me = new User();

            if (!$me->authenticate()) {
                $_SESSION['error'] = "Échec de l'authentification";
                header('Location: ' . Helpers::getLink('connexion-status'));
                exit();
            }
        }

        $userInfo = $this->userSQL->getInfo($connexion->getId());

        $authorityInfo = $this->authoritySQL->getInfo($userInfo['authority_id']);


        $groupeInfo = false;
        if ($authorityInfo['authority_group_id']) {
            $groupeInfo = $this->groupSQL->getInfo($authorityInfo['authority_group_id']);
        }

        return new InitData(
            $connexion,
            $me,
            $userInfo,
            $authorityInfo,
            $groupeInfo,
        );
    }

    public function initModule(InitData $initData, string $module_name, array $droit_specific = []): ModuleData
    {
        $moduleInfo = $this->moduleSQL->getInfoByName($module_name);
        $droitModuleInfo = $this->moduleSQL->getInfoModuleAuthority(
            $moduleInfo['id'],
            $initData->userInfo['authority_id']
        );
        $permUser = $this->moduleSQL->getInfoPerms($moduleInfo['id'], $initData->connexion->getId());
        $modulesInfo = $this->moduleSQL->getModulesForUser($initData->userInfo);

        if (
            ! $this->droit->canAccess(
                $moduleInfo,
                $initData->userInfo,
                $initData->authorityInfo,
                $initData->groupeInfo,
                $droitModuleInfo,
                $permUser,
                $droit_specific
            )
        ) {
            $this->s2lowRedirect->redirect('/', 'Accès refusé');
        }
        return new ModuleData(
            $moduleInfo,
            $permUser,
            $modulesInfo,
            $module_name
        );
    }
}
