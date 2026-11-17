<?php

namespace S2lowLegacy\Class;

use S2low\DTO\ModuleMenu;
use S2low\Enum\UserRole;
use S2low\Helpers\UserIdentityHelper;
use S2low\Services\UserAffiliation;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\MessageAdminSQL;

class MenuHTML
{
    private const NB_DAYS_BEFORE_CERTIFICATE_EXPIRE_WARNING = 90;

    private const NB_DAYS_BEFORE_CERTIFICATE_EXPIRE_DANGER = 7;

    public function getMenuContent(array $userInfo, $modulesInfo)
    {
        ob_start();
        ?>
        <div class="well sidebar-nav">
                    <?php if ($userInfo) : ?>
                        <?php $this->displayUserMenu($userInfo, $modulesInfo) ; ?>
                    <?php else : ?>
            <div id="menu-header">
                <a href="<?php echo WEBSITE_SSL ?>">Accéder au site</a><br />
                (Certificat nécessaire)
            </div>
                    <?php endif;?>
        </div>
        <?php
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }


    private function displayUserMenu($userInfo, $modulesInfo)
    {
        $logoutRoute = LegacyObjectsManager::getLegacyObjectInstancier()->get('router')->generate('app_logout');


        $userRole = UserRole::from($userInfo['role']);
        $moduleMenu = new ModuleMenu($userRole, $modulesInfo);

        $nb_days_before_certificate_expires = $this->getNbDaysBeforeCertificatExpire($userInfo);

        $objectInstancier  = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
        /** @var UserAffiliation $userAffiliation */
        $userAffiliation = $objectInstancier->get(UserAffiliation::class);
        $groups = $userAffiliation->getGroups(
            $userInfo['role'],
            $userInfo['authority_id'] ?? null,
            $userInfo['authority_group_id'] ?? null
        );
        $initials = UserIdentityHelper::getInitials(
            $userInfo['givenname'] ?? null,
            $userInfo['name'] ?? null
        ); ?>


                    <div id="menu-header">
                        <?php if ($userInfo['nb_user_with_my_certificate'] > 1) : ?>
                            <a class="menu-logout" href="<?php echo $logoutRoute ?>" title="Se déconnecter"><span>Se déconnecter</span></a>
                        <?php endif;?>
                        <div class="menu-identity">
                            <span class="menu-identity-initials" aria-hidden="true"><?php hecho($initials) ?></span>
                            <span class="menu-identity-civility">
                                <span class="menu-identity-name"><?php hecho($userInfo['givenname'] . ' ' . $userInfo['name']) ?></span>
                                <span class="menu-identity-role"><?php hecho($userRole->getLabel()) ?></span>
                            </span>
                        </div>
                        <?php foreach ($groups as $group) : ?>
                            <div class="menu-affiliation">
                                <span class="menu-affiliation-text">
                                    <span class="menu-affiliation-caption"><?php hecho($group->getCaption()) ?></span>
                                    <span class="menu-affiliation-name"><?php hecho($group->groupName) ?></span>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($nb_days_before_certificate_expires < self::NB_DAYS_BEFORE_CERTIFICATE_EXPIRE_DANGER) :?>
                        <div class="alert alert-danger message-admin">
                            <strong>Votre certificat expire dans <?php echo $nb_days_before_certificate_expires ?> jours !</strong>
                        </div>
                    <?php elseif ($nb_days_before_certificate_expires < self::NB_DAYS_BEFORE_CERTIFICATE_EXPIRE_WARNING) :?>
                        <div class="alert alert-warning message-admin">
                            <strong>Votre certificat expire dans <?php echo $nb_days_before_certificate_expires ?> jours !</strong>
                        </div>
                    <?php endif; ?>

                    <?php
                    /** @var MessageAdminSQL $messageAdminSQL */
                    $messageAdminSQL = $objectInstancier->get(MessageAdminSQL::class);
                    $messageAdmin = $messageAdminSQL->getPublishedMessage();
                    $messageAdmin->displayTitre();
                    ?>
                    <ul class="text-menu nav">
            <?php if (in_array($userInfo['role'], array('SADM','GADM','ADM'))) : ?>
            <li class="menu-list-title">Administration</li>
            <?php endif;?>
            
            <?php if ($userInfo['role'] == 'SADM') : ?>
                <li><a href="<?php echo Helpers::getLink("/admin/modules/admin_modules.php");?>">Gestion des modules</a></li>
                <li><a href="<?php echo Helpers::getLink('/admin/groups/admin_groups.php');?>">Gestion des groupes</a></li>
            <?php endif;?>
            <?php if (in_array($userInfo['role'], array('SADM','GADM'))) : ?>
                <li><a href="<?php echo Helpers::getLink('/admin/authorities/admin_authorities.php')?>">Gestion des collectivités</a></li>
            <?php endif?>
            <?php if ($userInfo['role'] == 'ADM') : ?>
                <li><a href="<?php echo Helpers::getLink('/modules/mail/index.php?command=annuaire');?>">Carnet d'adresses de la collectivité</a></li>
                <li><a href="<?php echo Helpers::getLink("/admin/authorities/admin_authority_edit.php?id=" . $userInfo["authority_id"]); ?>">Paramètres collectivité</a></li>
            <?php endif;?>
            <?php if (! in_array($userInfo['role'], [User::USER,User::ARCH])) : ?>
                <li><a href="<?php echo Helpers::getLink("/admin/users/admin_users.php");?>">Gestion des utilisateurs</a></li>
                <li><a href="<?php echo Helpers::getLink("/admin/services/admin_services.php");?>">Gestion des services</a></li>
            <?php endif;?>
        
        
        
             <?php if ($userInfo['role'] == 'SADM') : ?>            
                <li><a href="<?php echo Helpers::getLink("/admin/utilities/index.php");?>">Utilitaires système</a></li>
             <?php endif;?>
            <?php foreach ($moduleMenu->administrations as $module) : ?>
                <li><a href="<?php echo Helpers::getLink("/modules/" . $module->name . "/admin/index.php");?>">Utilitaires module <?php echo $module->label ?></a></li>
            <?php endforeach;?>



            <li class="menu-list-title">Modules</li>
            <?php if (count($moduleMenu->transactions) == 0) : ?>
                <li>Aucun module accessible</li>
            <?php endif;?>
            <?php foreach ($moduleMenu->transactions as $module) : ?>
                <li><a href="<?php echo  Helpers::getLink("/modules/" . $module->name . "/index.php"); ?>"><?php echo $module->label ?></a></li>
            <?php endforeach; ?>

            <?php if ($moduleMenu->exports) : ?>
                <li class="menu-list-title">Export des informations</li>
                <?php foreach ($moduleMenu->exports as $module) : ?>
                    <li><a href="<?php echo Helpers::getLink("/modules/" . $module->name . "/" . $module->name . "_export.php");?>">Exports module <?php echo $module->label ?></a></li>
                <?php endforeach;?>
            <?php endif; ?>


            <li class="menu-list-title">Suivi <?php echo ! in_array($userInfo['role'], [User::USER,User::ARCH]) ? "du site" : ""?></li>
            <li><a href="<?php echo Helpers::getLink("/common/logs_view.php");?>">Journal des événements</a></li>
            <?php foreach ($moduleMenu->statistics as $module) : ?>
                <li><a href="<?php echo Helpers::getLink("/modules/" . $module->name . "/" . $module->name . "_stats.php");?>">Statistiques module <?php echo $module->label ?></a></li>
            <?php endforeach;?>


        </ul>
        <?php
        return;
    }

    public function getNbDaysBeforeCertificatExpire($userInfo)
    {
        $x509Certificate = new X509Certificate();
        $expiration_time =  strtotime($x509Certificate->getExpirationDate($userInfo['certificate']));
        return floor(($expiration_time - time()) / 86400);
    }
}
