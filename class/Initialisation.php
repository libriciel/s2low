<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\JSONoutput;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Model\UserSQL;

/**
 * Gère la connection, l'authentification, la vérification des droits d'accès au module et l'instanciation
 * ( ce qui est un peu moche ) du FrontController, de jsonOutput et de HTMLOutput
 */
class Initialisation
{
    private string $html;
    /**
     * @var \S2lowLegacy\Lib\JSONoutput
     */
    private JSONoutput $JSONoutput;

    private FrontController $frontController;
    /**
     * @var \S2lowLegacy\Class\Connexion
     */
    private Connexion $connexion;
    /**
     * @var \S2lowLegacy\Model\UserSQL
     */
    private UserSQL $userSQL;
    /**
     * @var \S2lowLegacy\Model\AuthoritySQL
     */
    private AuthoritySQL $authoritySQL;
    /**
     * @var \S2lowLegacy\Model\GroupSQL
     */
    private GroupSQL $groupSQL;
    /**
     * @var \S2lowLegacy\Model\ModuleSQL
     */
    private ModuleSQL $moduleSQL;
    /**
     * @var \S2lowLegacy\Class\Droit
     */
    private Droit $droit;
    /**
     * @var \S2lowLegacy\Class\S2lowRedirect
     */
    private S2lowRedirect $s2lowRedirect;
    /**
     * @var array|mixed
     */
    private mixed $userInfo;
    /**
     * @var \S2lowLegacy\Class\User
     */
    private User $me;
    /**
     * @var false|mixed
     */
    private mixed $moduleInfo;
    private array|false $modulesInfo;
    /**
     * @var false|mixed
     */
    private mixed $groupeInfo;
    /**
     * @var false|mixed
     */
    private mixed $authorityInfo;
    private string $moduleName;
    /**
     * @var false|mixed
     */
    private mixed $permUser;

    /**
     * @param $html
     * @param \S2lowLegacy\Lib\JSONoutput $JSONoutput
     * @param \S2lowLegacy\Lib\FrontController $frontController
     * @param \S2lowLegacy\Class\Connexion $connexion
     * @param \S2lowLegacy\Model\UserSQL $userSQL
     * @param \S2lowLegacy\Model\AuthoritySQL $authoritySQL
     * @param \S2lowLegacy\Model\GroupSQL $groupSQL
     * @param \S2lowLegacy\Model\ModuleSQL $moduleSQL
     * @param \S2lowLegacy\Class\Droit $droit
     * @param \S2lowLegacy\Class\S2lowRedirect $s2lowRedirect
     */
    public function __construct(
        $html,
        JSONoutput $JSONoutput,
        FrontController $frontController,
        Connexion $connexion,
        UserSQL $userSQL,
        AuthoritySQL $authoritySQL,
        GroupSQL $groupSQL,
        ModuleSQL $moduleSQL,
        Droit $droit,
        S2lowRedirect $s2lowRedirect
    ) {
        $this->html = $html;
        $this->JSONoutput = $JSONoutput;
        $this->frontController = $frontController;
        $this->connexion = $connexion;
        $this->userSQL = $userSQL;
        $this->authoritySQL = $authoritySQL;
        $this->groupSQL = $groupSQL;
        $this->moduleSQL = $moduleSQL;
        $this->droit = $droit;
        $this->s2lowRedirect = $s2lowRedirect;
    }

    /**
     * @param string $module_name
     * @param array $droit_specific
     * @return void
     */
    public function init(string $module_name = '', array $droit_specific = []): void
    {
        $this->moduleName = $module_name;
        if (!$this->connexion->isConnected()) {
            $this->me = new User();

            if (!$this->me->authenticate()) {
                $_SESSION['error'] = "Échec de l'authentification";
                header('Location: ' . Helpers::getLink('connexion-status'));
                exit();
            }
        }

        $this->userInfo = $this->userSQL->getInfo($this->getUserId());

        $this->authorityInfo = $this->authoritySQL->getInfo($this->userInfo['authority_id']);


        $this->groupeInfo = false;
        if ($this->authorityInfo['authority_group_id']) {
            $this->groupeInfo = $this->groupSQL->getInfo($this->authorityInfo['authority_group_id']);
        }

        if (!empty($module_name)) {
            $this->moduleInfo = $this->moduleSQL->getInfoByName($module_name, $this->userInfo['authority_id']);
            $droitModuleInfo = $this->moduleSQL->getInfoModuleAuthority(
                $this->moduleInfo['id'],
                $this->userInfo['authority_id']
            );
            $this->permUser = $this->moduleSQL->getInfoPerms($this->moduleInfo['id'], $this->getUserId());

            if (
                !$this->droit->canAccess(
                    $this->moduleInfo,
                    $this->userInfo,
                    $this->authorityInfo,
                    $this->groupeInfo,
                    $droitModuleInfo,
                    $this->permUser,
                    $droit_specific
                )
            ) {
                $this->s2lowRedirect->redirect('/', 'Accès refusé');
            }
        }

        $this->modulesInfo = $this->moduleSQL->getModulesForUser($this->userInfo);
    }

    /**
     * @return void
     */
    public function initActes(): void
    {
        $this->init('actes', ['CS', 'TT']);
    }

    /**
     * @return void
     */
    public function initHelios(): void
    {
        $this->init('helios');
    }

    /**
     * @return void
     */
    public function initMailSec(): void
    {
        $this->init('mail');
    }

    public function userIsSuperAdmin(): bool
    {
        return $this->droit->isSuperAdmin($this->userInfo);
    }

    public function getUser(): User
    {
        return $this->me;
    }

    public function getUserInfo()
    {
        return $this->userInfo;
    }

    public function getModulesInfo()
    {
        return $this->modulesInfo;
    }

    public function userCanSign(): bool
    {
        return $this->moduleSQL->hasDroit(
            $this->moduleInfo['id'],
            $this->getUserId(),
            'CS'
        );
    }

    public function userIsInAuthority(int $authority_id)
    {
        return $this->userInfo['authority_id'] != $authority_id;
    }
    public function userIsGroupAdmin()
    {
        return $this->droit->isGroupAdmin($this->userInfo);
    }

    public function userIsAuthorityAdmin()
    {
        return $this-> droit->isAuthorityAdmin($this->userInfo);
    }
    public function getGroupe()
    {
        return $this->groupeInfo;
    }

    public function getAuthorityInfo()
    {
        return $this->authorityInfo;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->connexion->getId();
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function userIsAdmin()
    {
        return $this->droit->isAdmin($this->userInfo);
    }

    public function permUserIs(string $perm)
    {
        return $this->permUser == $perm;
    }

    public function userHasDroit(mixed $authorityFiltreInfo): bool
    {
        return $this->droit->hasDroit($this->userInfo, $authorityFiltreInfo);
    }
}
