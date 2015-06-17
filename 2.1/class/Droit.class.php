<?php 


class Droit {
	
	public function canAccess($moduleInfo,$userInfo,$authorityInfo,$groupeInfo,$droitModuleInfo,$permUser, $droit_specific = array()){	
		
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
			if (! in_array($permUser,$droit_specific)){
				return false;
			}
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
	
	public function isGroupAdmin(array $userInfo){
		return $userInfo['role'] == 'GADM';
	}	
	
	public function isAuthorityAdmin(array $userInfo){
		return $userInfo['role'] == 'ADM';
	}	
	
	public function hasDroit(array $userInfo, array $authorityInfo){

		if ($this->isSuperAdmin($userInfo)){
			return true;
		}
		
		if ( ! $this->isGroupAdmin($userInfo) && ! $this->isAuthorityAdmin($userInfo)){
			return false;
		}
		return $authorityInfo['authority_group_id'] == $userInfo['authority_group_id'];
		
	}
	
	
}