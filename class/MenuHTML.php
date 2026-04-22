<?php

namespace S2lowLegacy\Class;

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

        $module_admin = array();
        $module_stat = array();
        $module_export = array();


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
                        Bienvenue <?php hecho($userInfo['pretty_name']) ?><br />

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
                        Rôle <?php  echo $userInfo['role_str'] ?>
            <?php if ($userInfo['nb_user_with_my_certificate'] > 1) : ?>
            <br/><a href='<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("logout.php")?>'>déconnexion</a>
            <?php endif;?>
                    </div>
                    <ul class="text-menu nav">
            <?php if (in_array($userInfo['role'], array('SADM','GADM','ADM'))) : ?>
            <li class="menu-list-title">Administration</li>
            <?php endif;?>
            
            <?php if ($userInfo['role'] == 'SADM') : ?>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/modules/admin_modules.php");?>">Gestion des modules</a></li>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/admin/groups/admin_groups.php');?>">Gestion des groupes</a></li>
            <?php endif;?>
            <?php if (in_array($userInfo['role'], array('SADM','GADM'))) : ?>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/admin/authorities/admin_authorities.php')?>">Gestion des collectivités</a></li>
            <?php endif?>
            <?php if ($userInfo['role'] == 'ADM') : ?>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/mail/index.php?command=annuaire');?>">Carnet d'adresses de la collectivité</a></li>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/authorities/admin_authority_edit.php?id=" . $userInfo["authority_id"]); ?>">Paramètres collectivité</a></li>
            <?php endif;?>
            <?php if (! in_array($userInfo['role'], [User::USER,User::ARCH])) : ?>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/users/admin_users.php");?>">Gestion des utilisateurs</a></li>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/services/admin_services.php");?>">Gestion des services</a></li>
            <?php endif;?>
        
        
        
             <?php if ($userInfo['role'] == 'SADM') : ?>            
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/utilities/index.php");?>">Utilitaires système</a></li>
             <?php endif;?>
            <?php foreach ($module_admin as $module) : ?>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/" . $module["name"] . "/admin/index.php");?>">Utilitaires module <?php echo $module["name"] ?></a></li>
            <?php endforeach;?>



            <li class="menu-list-title">Modules</li>
            <?php if (count($modulesInfo) == 0) : ?>
                <li>Aucun module accessible</li>
            <?php endif;?>
            <?php foreach ($modulesInfo as $module) : ?>
                <li><a href="<?php echo  \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/" . $module["name"] . "/index.php"); ?>"><?php echo $module["menu_entry"] ?></a></li>
            <?php endforeach; ?>

            <?php if ($module_export && in_array($userInfo['role'], array('ADM','GADM','SADM'))) : ?>
                <li class="menu-list-title">Export des informations</li>
                <?php foreach ($module_export as $module) : ?>
                    <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/" . $module["name"] . "/" . $module["name"] . "_export.php");?>">Exports module <?php echo $module["name"] ?></a></li>
                <?php endforeach;?>
            <?php endif; ?>


            <li class="menu-list-title">Suivi <?php echo ! in_array($userInfo['role'], [User::USER,User::ARCH]) ? "du site" : ""?></li>
            <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/common/logs_view.php");?>">Journal des événements</a></li>
            <?php foreach ($module_stat as $module) : ?>
                <li><a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/" . $module["name"] . "/" . $module["name"] . "_stats.php");?>">Statistiques module <?php echo $module["name"] ?></a></li>
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
