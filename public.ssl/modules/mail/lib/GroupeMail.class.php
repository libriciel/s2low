<?php 
require_once(SITEROOT . "/class/DataObject.class.php");

class GroupeMail extends DataObject {	
	
	protected $objectName="mail_groupe";
	protected $id;
	protected $authority_id;
	protected $name;
	
	protected $dbFields =  array(
		"authority_id"      	=> array( "descr" => "Identifiant mail", "type" => "isInt", "mandatory" => true),
		"name"  => array("descr" =>"---", "type" =>"isString", "mandatory"=>true),
	);	
	
	public function __construct($id=false) {
		
    	parent::__construct($id);
  	}
	
	public function addUser($id){
		assert('$this->id');
		$sql = "SELECT * FROM mail_user_groupe WHERE id_user = $id AND id_groupe = ".$this->id;
		$result = $this->db->select($sql);
		if ($result->num_row() != 0){
			return;
		}
		
		$sql = "INSERT INTO mail_user_groupe(id_user,id_groupe) VALUES ($id,".$this->id.")";
		$this->db->exec($sql);
	}
  	
  	public function removeUser($id){
  		assert('$this->id');
  		$sql = "DELETE FROM mail_user_groupe WHERE id_user=$id AND id_groupe=".$this->id;
  		$this->db->exec($sql);
  	}

  	public function isUserInGroup($id_user){
  	    $sql = "SELECT count(*) as nb FROM mail_user_groupe WHERE id_user=$id_user";
  	    $nb_groupe = $this->db->getOneValue($sql);
  	    return $nb_groupe != 0;
    }

	
	public function getGroupeIdFromName($name,$authority_id){
		
		$db =DatabasePool::getInstance();
		
		$sql = "SELECT id FROM mail_groupe WHERE name=".$db->quote($name)." AND authority_id=$authority_id";
		
		$result = $db->select($sql);
		if ($result->num_row() == 0){
			return false;
		}
		$r =  $result->get_next_row();
		return $r['id'];		
	}
	
	public function getGroupeByAuthorityId($authority_id){
		$sql = "SELECT * " . 
				" FROM mail_groupe ".
				" WHERE authority_id=$authority_id ".
				" ORDER BY mail_groupe.name";
		$result = $this->db->select($sql);
		
		$tabResult= array();
		
		while ($ligne = $result->get_next_row()){
			$tabResult[$ligne['id']] = $ligne;
			$tabResult[$ligne['id']]['nb_contact'] = 0;	
		}
		$sql = 	"SELECT count(*) as nb,id_groupe FROM mail_user_groupe ".
				" JOIN mail_groupe ON mail_user_groupe.id_groupe=mail_groupe.id ". 
				" WHERE authority_id=$authority_id " .
				" GROUP BY mail_user_groupe.id_groupe";
		$result = $this->db->select($sql);
		while ($ligne = $result->get_next_row()){
			$tabResult[$ligne['id_groupe']]['nb_contact'] = $ligne['nb'];	
		}
		
		return $tabResult;				
	}	
	
	public function getNbUtilisateur(){
		assert('$this->id');
		$sql = "SELECT count(*) as nb FROM mail_user_groupe WHERE id_groupe=".$this->id;
		$result = $this->db->select($sql);
		$ligne = $result->get_next_row();
		return $ligne['nb'];
	}
	
}