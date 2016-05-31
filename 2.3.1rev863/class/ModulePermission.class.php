<?php 
//Attention, cette classe n'est pas utilisée partout EP
class ModulePermission {
	
	private $service;
	private $module_name;
	
	public function __construct(ServiceUser $service,$module_name){
		$this->service = $service;
		$this->module_name = $module_name;		
	}
	
	public function canView(User $me,User $owner){
		
		if ($this->canWrite($me,$owner)){
			return true;	
		}
		return $this->service->areCollegues($me->getId(),$owner->getId());
			
	}
	
	public function canWrite(User $me,User $owner){
		if ($me->isSuper()){
			return true;
		}
		
		if (! $me->canAccess($this->module_name)){
			return false;
		}
		
		if ($me->isAuthorityAdmin() && ($me->get("authority_id") == $owner->get("authority_id"))){
			return true;
		}
		return $me->getId()==$owner->getId();	
	}
	
}