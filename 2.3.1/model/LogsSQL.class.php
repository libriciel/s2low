<?php

class LogsSQL extends SQL {

	const LEVEL_DEBUG = 0;
	const LEVEL_INFO = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_ERROR = 3;
	const LEVEL_CRITICAL = 4;


	public function getLogLevelList(){
		return array(0=>"Debug","Information","Warning","Error","Critical");
	}

	public function getList($authority_group_id,$authority_id,$user_id,$user_name,$module,$severity,$message,$visibility,$offset,$limit){
		$data_to_retrieve = " logs.id,logs.date,logs.severity,logs.module,logs.issuer,logs.user_id,logs.visibility,logs.message, " .
							" logs.authority_id, users.name, users.givenname, users.login ";

		$offset = intval($offset);
		$limit = intval($limit);
		$end_query = " ORDER BY id DESC LIMIT $limit OFFSET $offset";

		return $this->getListQuery($data_to_retrieve,$end_query,$authority_group_id,$authority_id,$user_id,$user_name,$module,$severity,$message,$visibility);
	}


	public function getNbLog($authority_group_id,$authority_id,$user_id,$user_name,$module,$severity,$message,$visibility){
		$data =  $this->getListQuery("count(*)",false,$authority_group_id,$authority_id,$user_id,$user_name,$module,$severity,$message,$visibility);
		return $data[0]['count'];
	}

	private function getListQuery($data_to_retrieve,$end_query,$authority_group_id,$authority_id,$user_id,$user_name,$module,$severity,$message,$visibility){
		$sql = "SELECT $data_to_retrieve ".
			" FROM logs" .
			" JOIN users ON users.id=logs.user_id " ;

		$where = array("1=1");
		$data = array();


		if ($authority_group_id){
			$where[] = " logs.authority_group_id = ? ";
			$data[] = $authority_group_id;
		}

		if ($authority_id){
			$where[] = " logs.authority_id = ? ";
			$data[] = $authority_id;
		}

		if ($user_id){
			$where[] = "logs.user_id=?";
			$data[] = $user_id;
		}

		if ($user_name){
			$where[] = "(users.name ILIKE ? OR users.givenname ILIKE ?) ";
			$data[] = "%$user_name%";
			$data[] = "%$user_name%";
		}

		if($module){
			$where[] = "logs.module=?";
			$data[] = $module;
		}

		if ($severity != -1){
			$where[] = "logs.severity=?";
			$data[] = $severity;
		}

		if ($message){
			$where[] = "logs.message ILIKE ?";
			$data[] = "%$message%";
		}
		if ($visibility){
			$visibility_str = "'".implode("','",$visibility)."'";
			$where[] = "visibility IN ($visibility_str)";
		}

		$sql .= " WHERE ". implode(" AND ",$where);

		$sql .= $end_query;
		return $this->query($sql,$data);
	}

}