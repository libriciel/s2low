<?php 

class ServiceUser {
	
	function __construct(Database $db) {
		$this->db = $db;
  	}
	
	function add($name,$authority_id){
		$sql = "SELECT * FROM service_user WHERE name='$name' AND authority_id=$authority_id";
		$result = $this->db->select($sql);
		if ($result->num_row() != 0){
			return false;
		}
		
		$sql = "INSERT INTO service_user(name,authority_id) VALUES ('$name',$authority_id)";
		$this->db->exec($sql);
		return true;
	}
	
	function getServiceUser($authority_id ){
		if ($authority_id == null){
			return array();
		}
		$sql = "SELECT service_user.* FROM service_user WHERE authority_id =$authority_id ORDER by service_user.name";
		$result = $this->db->select($sql);
		$tabResult = array();
		while ($ligne = $result->get_next_row()){
			$tabResult[] = $ligne;
		}
		return $tabResult;		
	}
	
	function getPossibleParent($authority_id,$service_id){
		$result = array();
		
		$enfant = array($service_id);
		do {
			$old_enfant = $enfant;
			$enfant = $this->getServiceEnfant($old_enfant);
		} while($enfant != $old_enfant);
			
		$allService = $this->getServiceUser($authority_id);
		foreach($allService as $service){
			if (in_array($service['id'],$enfant)){
				continue;
			}
			
			$result[] = $service;
		}
		return $result;
	}
	
	function getInfo($id){
		return $this->db->getOneLine("SELECT s1.*,s2.name as parent_name FROM service_user s1 LEFT JOIN service_user s2 ON s1.parent_id=s2.id where s1.id=$id");
	}
	
	function getListUser($id){
		return $this->db->fetchAll("SELECT * FROM service_user_content JOIN users ON service_user_content.id_user=users.id WHERE id_service=$id");		
	}
  	
	function addUser($id_user,$id_service){
		$l = $this->db->getOneLine("SELECT * FROM service_user_content WHERE id_user=$id_user AND id_service=$id_service");
		if ($l){
			return ;
		}
		$this->db->select("INSERT INTO service_user_content(id_user,id_service) VALUES ($id_user,$id_service)");	
	}
	
	function getServiceFromUser($id_user){
		return $this->db->fetchAll("SELECT * FROM service_user_content JOIN service_user ON service_user_content.id_service=service_user.id WHERE service_user_content.id_user=$id_user");		
	}
	
	function enleverUser($id_service,$id_user){
		$this->db->select("DELETE FROM service_user_content WHERE id_service=$id_service AND id_user=$id_user");
	}
	
	function supprimerService($id_service){
		$this->db->select("DELETE FROM service_user WHERE id=$id_service");
	}
	
	function getMesCollegues($id_user){
		
		$sql = "SELECT DISTINCT id_service FROM service_user_content WHERE id_user=$id_user";
		$result = $this->db->fetchAll($sql);
		
		$id_service = array();
		foreach($result as $info){
			$id_service[] = $info['id_service'];
		}
		$old_service = array();
		while ($id_service != $old_service){
			$old_service = $id_service;
			$id_service = $this->getServiceEnfant($id_service);
		}
		
		$ids = implode(',',$id_service);
		
		if (!$ids){
			return array();
		}
		
		$result = $this->db->fetchAll("SELECT DISTINCT id_user FROM service_user_content WHERE id_service IN ($ids) ");	
		return $result;
	}
	
	private function getServiceEnfant(array $id_service){
		$ids = implode(',',$id_service);
		$result = $this->db->fetchAll("SELECT DISTINCT id FROM service_user WHERE parent_id IN ($ids)");
		foreach($result as $info){
			$id_service[] = $info['id'];
		}
		return array_unique($id_service);	
	}
	
	public function getAllEnfant($id_service){
		$sql = "SELECT service_user.* FROM service_user WHERE parent_id = $id_service";
		return $this->db->fetchAll($sql);
	}
	
	function areCollegues($id_user1,$id_user2){
		if ($this->areInSameService($id_user1,$id_user2)){
			return true;
		}
		$collegues = $this->getMesCollegues($id_user1);
		foreach ($collegues as $info){
			if ($info['id_user'] == $id_user2){
				return true;
			}
		}
		return false;
	}
	
	private function areInSameService($id_user1,$id_user2){
		$sql =  " SELECT * FROM service_user_content as s1 " . 
				" JOIN service_user_content as s2 ON s1.id_service=s2.id_service ".
				" WHERE s1.id_user=$id_user1 AND s2.id_user=$id_user2";
		return $this->db->getOneLine($sql);			
	}
	
	public function addParent($id_service,$id_parent){
		if (!$id_parent){
			$id_parent = "NULL";
		}
		$sql = "UPDATE service_user SET parent_id=$id_parent WHERE id = $id_service";
		$this->db->select($sql);
	}

	public function removeParent($id_service){
		$this->addParent($id_service,false);
	}

}