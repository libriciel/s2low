<?php 

class Pastell {
	
	private $url;
	private $id_e;
	private $login;
	private $password;
	
	private $lastError;
	
	public function __construct($url,$id_e,$login,$password){
		$this->url = $url;
		$this->id_e = $id_e;
		$this->login = $login;
		$this->password = $password;	
	}
	
		
	public function getLastError(){
		return $this->lastError;
	}
	
	private function callAPI($url,array $postData = array(),$postFile = array()){
		$curl_wrapper = new CurlWrapper();
		$curl_wrapper->httpAuthentication($this->login, $this->password);
		
		foreach($postData as $name => $value){
			$curl_wrapper->addPostData($name, $value);
		}
		foreach($postFile as $field => $file_info){
			$curl_wrapper->addPostFile($field, $file_info[0],$file_info[1]);
		}
				
		$data = $curl_wrapper->get($this->url."/".$url);
		
		if (!$data){
			$this->lastError = $curl_wrapper->getLastError();
			return false;
		}
		$data = json_decode($data,true);
		
		if (isset($data['status']) && $data['status']=='error' ){
			$this->lastError = "Message de Pastell : " . utf8_decode($data['error-message']);
			return false;
		}
		return $data;
	} 
	

	public function testConnexion(){
		$data = $this->callAPI("list-entite.php");
		if (! $data){
			$this->lastError = "Impossible de lire des données depuis Pastell.";
			return false;
		}
		foreach($data as $entite){
			if ($entite['id_e'] == $this->id_e){
				return true;
			}
		}
		$this->lastError = "L'entité Pastell « {$this->id_e} » n'est pas autorisé pour l'utilisateur « {$this->login} ».";
		return false;
	}
	
	
	public function createActes($transactionInfo){
		$result = $this->callAPI("create-document.php?id_e={$this->id_e}&type=actes-generique");
		$id_d = $result['id_d'];
		$info = array(	'id_e' => $this->id_e,
						'id_d'=>$id_d,
						'acte_nature' => $transactionInfo['nature_code'],
						'numero_de_lacte' => $transactionInfo['number'],
						'objet'=>$transactionInfo['subject'],
						'date_de_lacte' => $transactionInfo['decision_date'],
						'classification' => $transactionInfo['classification'],
						'envoi_sae' => 1,
		);
		
		$result = $this->callAPI("modif-document.php",$info);
		if (!$result){
			return false;
		}
		return $id_d;
	}
	
	public function createHelios($transactionInfo){
		$result = $this->callAPI("create-document.php?id_e={$this->id_e}&type=helios-generique");
		$id_d = $result['id_d'];
		$info = array(	'id_e' => $this->id_e,
						'id_d'=>$id_d,
						'objet' => $transactionInfo['filename'],
						'tedetis_transaction_id' => $transactionInfo['id'],
						'envoi_sae' => 1,
		);
		
		$result = $this->callAPI("modif-document.php",$info);
		if (!$result){
			return false;
		}
		return $id_d;
	}
	
	public function postFile($id_d,$field,$file_path,$file_orig_name = false){
		return $this->callAPI("modif-document.php",
					array('id_e'=>$this->id_e,'id_d'=>$id_d),
					array($field=>array($file_path,$file_orig_name)));
	}
	
	public function postActes($id_d,$actes_file_path,$actes_orig_file_name){
		$this->postFile($id_d,'arrete',$actes_file_path,$actes_orig_file_name);
	}
	
	public function postAnnexe($id_d,$annexe_path,$annexe_orig_name){
		$this->postFile($id_d,'autre_document_attache',$annexe_path,$annexe_orig_name);
	}
	
	public function postARActes($id_d,$ar_acte_path){
		$this->postFile($id_d,'aractes',$ar_acte_path);
	}
	
	public function postRelatedTransaction($id_d,$echange_prefecture_type,$echange_prefecture,$echange_prefecture_ar){
		foreach($echange_prefecture as $ep){
			$this->postFile($id_d,'echange_prefecture',$ep[0],$ep[1]);
		}
		foreach($echange_prefecture_ar as $ep){
			$this->postFile($id_d,'echange_prefecture_ar',$ep[0],$ep[1]);
		}
		$info = array('id_e'=>$this->id_e,'id_d'=>$id_d);
		foreach($echange_prefecture_type as $i => $type){
			$info['echange_prefecture_type_'.$i] = $type;
		}
		$this->callAPI("modif-document.php",$info);
	}
	
	public function sendSAE($id_d){
		$info = array('id_e'=>$this->id_e,'id_d'=>$id_d,'action'=>'send-archive');
		return $this->callAPI("action.php",$info);
	}
	
	public function getInfo($id_d){
		$info = array('id_e'=>$this->id_e,'id_d'=>$id_d);
		return $this->callAPI("detail-document.php",$info);
	}
	
	public function getFile($id_d,$field){
		$url = "recuperation-fichier.php?id_e={$this->id_e}&id_d=$id_d&field=$field";
		$curl_wrapper = new CurlWrapper();
		$curl_wrapper->httpAuthentication($this->login, $this->password);
		return $curl_wrapper->get($this->url."/".$url);
	}
	
}