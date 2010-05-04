<?php 

//Attention, cette classe n'est pas utilisé partout EP

class ActesPermission {
	
	//FIXME : il y a sans dout une meilleure place...
	const MODULE_NAME = "actes";
	
	private $service;
	
	public function __construct(ServiceUser $service){
		$this->service = $service;
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
		
		if (! $me->canAccess(self::MODULE_NAME)){
			return false;
		}
		
		if ($me->isAuthorityAdmin() && ($me->get("authority_id") == $owner->get("authority_id"))){
			return true;
		}
		return $me->getId()==$owner->getId();	
	}
	
}