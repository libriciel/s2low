<?php 

class MenuHTML  {

	public function getMenu(array $userInfo,$modulesInfo) {
 	
 		ob_start();
 	?>
            <div id="sidebar" class="col-md-3" role="navigation">
                <div class="well sidebar-nav">
                    <?php if ($userInfo): ?>
                        <?php $this->displayUserMenu($userInfo,$modulesInfo) ; ?>
                    <?php else: ?>
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
 
	private function displayUserMenu($userInfo,$modulesInfo) {

		$module_admin = array();
		$module_stat = array();
		
		foreach ($modulesInfo as $i => $module) {
			
			if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/" . $module["name"] . "_stats.php")){
				$module_stat[] = $module;
			}
			if (file_exists(SITEROOT . "/public.ssl/modules/" . $module["name"] . "/admin/index.php")){
				if (in_array($userInfo['role'],array('SADM','GADM'))) {
					$module_admin[] = $module;
				}
			}
		 }
		 
		?>
                    <div id="menu-header">
                        Bienvenue <?php echo $userInfo['pretty_name'] ?><br />
                        <?php
                    global $objectInstancier;
                    /** @var MessageAdminSQL $messageAdminSQL */
                    $messageAdminSQL = $objectInstancier->get('MessageAdminSQL');
                    $messageAdmin = $messageAdminSQL->getPublishedMessage();
                    $messageAdmin->displayTitre();

                        ?>
                        Rôle <?php  echo $userInfo['role_str'] ?>
			<?php if ($userInfo['nb_user_with_my_certificate'] > 1 ) : ?>
			<br/><a href='<?php echo WEBSITE_SSL ?>/logout.php'>déconnexion</a>
			<?php endif;?>
                    </div>
                    <ul class="text-menu nav">
 			<?php if (in_array($userInfo['role'],array('SADM','GADM','ADM'))) : ?>
 			<li class="menu-list-title">Administration</li>
 			<?php endif;?>
 			
 			<?php if ($userInfo['role'] == 'SADM') : ?>
 				<li><a href="<?php echo WEBSITE_SSL ?>/admin/modules/admin_modules.php">Gestion des modules</a></li>
				<li><a href="<?php echo WEBSITE_SSL ?>/admin/groups/admin_groups.php\">Gestion des groupes</a></li>
			<?php endif;?>
 			<?php if (in_array($userInfo['role'],array('SADM','GADM'))) : ?>
 				<li><a href="<?php echo WEBSITE_SSL ?>/admin/authorities/admin_authorities.php">Gestion des collectivités</a></li>
			<?php endif?>
			<?php if ($userInfo['role'] == 'ADM') : ?>
 				<li><a href="<?php echo WEBSITE_SSL ?>/modules/mail/index.php?command=annuaire">Carnet d'adresses de la collectivité</a></li>
 				<li><a href="<?php echo WEBSITE_SSL ?>/admin/authorities/admin_authority_edit.php?id=<?php echo $userInfo["authority_id"] ?>">Paramètres collectivité</a></li>			
 			<?php endif;?>
 			<?php if ($userInfo['role'] != 'USER') : ?>
 				
 				<li><a href="<?php echo WEBSITE_SSL ?>/admin/users/admin_users.php">Gestion des utilisateurs</a></li>
				<li><a href="<?php echo WEBSITE_SSL ?>/admin/services/admin_services.php">Gestion des services</a></li>	
 			<?php endif;?>
 		
 		
 		
 			 <?php if ($userInfo['role'] == 'SADM') : ?>			
				<li><a href="<?php echo WEBSITE_SSL ?>/admin/utilities/index.php">Utilitaires système</a></li>
 			<?php endif;?>
 			<?php foreach ($module_admin as $module) : ?>
 				<li><a href="<?php echo WEBSITE_SSL ?>/modules/<?php echo $module["name"] ?>/admin/index.php">Utilitaires module <?php echo $module["name"] ?></a></li>
 			<?php endforeach;?>

	

 			
 			<li class="menu-list-title">Modules</li>
 			<?php if (count($modulesInfo) == 0): ?>
 				<li>Aucun module accessible</li>
 			<?php endif;?>
 			<?php foreach ($modulesInfo as $module) : ?>
 				<li><a href="<?php echo WEBSITE_SSL ?>/modules/<?php echo $module["name"] ?>"><?php echo $module["menu_entry"] ?></a></li>
 			<?php endforeach; ?>
 			<li class="menu-list-title">Suivi <?php echo $userInfo['role'] != 'USER' ? "du site" :""?></li>
			<li><a href="<?php echo WEBSITE_SSL ?>/common/logs_view.php">Journal des événements</a></li>
			<?php foreach ($module_stat as $module) : ?>
				<li><a href="<?php echo WEBSITE_SSL ?>/modules/<?php echo $module["name"] ?>/<?php echo $module["name"]?>_stats.php">Statistiques module <?php echo $module["name"] ?></a></li>
			<?php endforeach;?>
 		</ul>
 	<?php 
		return;
  }
}