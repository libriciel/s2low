<?php

class AuthorityTypesSQL extends SQL {

	public function getChildList() {
		$sql = "SELECT id, description " .
				" FROM authority_types " .
				" WHERE parent_type_id IS NOT NULL " .
				" ORDER BY id ";

		return $this->query($sql);
	}

}