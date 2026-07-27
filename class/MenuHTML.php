<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\AuthoritySQL;
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

        $module_admin = array();
        $module_stat = array();
        $module_export = array();
        $logoutRoute = LegacyObjectsManager::getLegacyObjectInstancier()->get('router')->generate('app_logout');


        foreach ($modulesInfo as $i => $module) {
            if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/" . $module["name"] . "_stats.php")) {
                $module_stat[] = $module;
            }
            if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/admin/index.php")) {
                if (in_array($userInfo['role'], array('SADM','GADM'))) {
                    $module_admin[] = $module;
                }
            }
            if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/" . $module["name"] . "_export.php")) {
                $module_export[] = $module;
            }
        }

        $nb_days_before_certificate_expires = $this->getNbDaysBeforeCertificatExpire($userInfo); ?>


                    <div id="menu-header">
                        <div class="menu-header-head">
                            <div class="menu-header-hello">Bienvenue</div>
                            <div class="menu-header-name"><?php hecho($userInfo['pretty_name']) ?></div>
                        </div>
                        <div class="menu-header-body">

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
                        $objectInstancier  = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
                    /** @var MessageAdminSQL $messageAdminSQL */
                        $messageAdminSQL = $objectInstancier->get(MessageAdminSQL::class);
                        $messageAdmin = $messageAdminSQL->getPublishedMessage();
                        $messageAdmin->displayTitre();

                        ?>
                        <div class="menu-header-row">
                            <span class="menu-header-label">Rôle</span>
                            <span class="menu-header-value"><?php  echo $userInfo['role_str'] ?></span>
                        </div>
                        <?php if ($userInfo['role'] === User::ADM) : ?>
                            <?php
                            /** @var AuthoritySQL $authoritySQL */
                            $authoritySQL = $objectInstancier->get(AuthoritySQL::class);
                            $authorityInfo = $authoritySQL->getInfo($userInfo['authority_id']);
                            ?>
                            <div class="menu-header-row">
                                <span class="menu-header-label">Collectivité</span>
                                <span class="menu-header-value"><?php hecho($authorityInfo['name']) ?></span>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>
            <?php if ($userInfo['nb_user_with_my_certificate'] > 1) : ?>
                    <a class="menu-logout" href="<?php echo $logoutRoute ?>">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Se déconnecter
                    </a>
            <?php endif;?>
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
            <?php foreach ($module_admin as $module) : ?>
                <li><a href="<?php echo Helpers::getLink("/modules/" . $module["name"] . "/admin/index.php");?>">Utilitaires module <?php echo $module["name"] ?></a></li>
            <?php endforeach;?>



            <li class="menu-list-title">Modules</li>
            <?php if (count($modulesInfo) == 0) : ?>
                <li>Aucun module accessible</li>
            <?php endif;?>
            <?php foreach ($modulesInfo as $module) : ?>
                <li><a href="<?php echo  Helpers::getLink("/modules/" . $module["name"] . "/index.php"); ?>"><?php echo $module["menu_entry"] ?></a></li>
            <?php endforeach; ?>

            <?php if ($module_export && in_array($userInfo['role'], array('ADM','GADM','SADM'))) : ?>
                <li class="menu-list-title">Export des informations</li>
                <?php foreach ($module_export as $module) : ?>
                    <li><a href="<?php echo Helpers::getLink("/modules/" . $module["name"] . "/" . $module["name"] . "_export.php");?>">Exports module <?php echo $module["name"] ?></a></li>
                <?php endforeach;?>
            <?php endif; ?>


            <li class="menu-list-title">Suivi <?php echo ! in_array($userInfo['role'], [User::USER,User::ARCH]) ? "du site" : ""?></li>
            <li><a href="<?php echo Helpers::getLink("/common/logs_view.php");?>">Journal des événements</a></li>
            <?php foreach ($module_stat as $module) : ?>
                <li><a href="<?php echo Helpers::getLink("/modules/" . $module["name"] . "/" . $module["name"] . "_stats.php");?>">Statistiques module <?php echo $module["name"] ?></a></li>
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
