<?php 


class Droit {
	
	public function canAccess($moduleInfo,$userInfo,$authorityInfo,$groupeInfo,$droitModuleInfo,$permUser){	
		
		if ( ! $moduleInfo ){
			return false;
		}

		if ($moduleInfo['status'] != 1){
			return false;
		}
		
		if (! $userInfo || $userInfo['status'] != 1){
			return false;
		}
	
		if (! $authorityInfo || $authorityInfo['status'] != 1){
			return false;
		}
		if ( $groupeInfo && $groupeInfo['status'] != 1){
			sortir("Échec de l'authentification");
		}
		if ($this->isGroupOrSuperAdmin($userInfo)){
			return true;
		}
		if (! $droitModuleInfo){
			return false;
		}
		if (! in_array($permUser , array('RO','RW'))){
			return false;
		}
		
		return true;
	}
	
	public function isGroupOrSuperAdmin(array $userInfo){
		return in_array($userInfo['role'],array('SADM','GADM'));
	}
	
	public function isSuperAdmin(array $userInfo){
		return $userInfo['role'] == 'SADM';
	}
	
	public function isAdmin(array $userInfo){
		return in_array($userInfo['role'],array('SADM','GADM','ADM'));
	}
	
}