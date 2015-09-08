<?php

class HeliosRetourSQL extends SQL {
	
	public function create($siren,$filename){
		$sql = "INSERT INTO helios_retour(id, siren, filename, status, date) " .
			" VALUES (nextval('helios_transactions_workflow_id_seq'), ?,?,0,now()) ";
		$this->query($sql,$siren,$filename);
	}
	
}