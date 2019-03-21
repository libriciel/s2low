<?php

class ActesTypePJSQL extends SQL {

    public function getAll(){
        $sql = "SELECT * FROM actes_type_pj ORDER BY nature_id,code";
        return $this->query($sql);
    }

    public function getCodeList(){
		$sql = "SELECT code FROM actes_type_pj ORDER BY nature_id,code";
		return $this->queryOneCol($sql);
	}

	public function getListByNature(){
		$result = [];
		foreach($this->getAll() as $type){
			$result[$type['nature_id']][$type['code']] =   "{$type['libelle']} ({$type['code']})";
		}
		foreach($result as $nature => $typologie_list){

			$to_add = [];
			foreach ($typologie_list as $code => $libelle){
				if (substr($code,0,3)=='99_'){
					unset($result[$nature][$code]);
					$to_add[$code] = $libelle;
				}
			}
			asort($result[$nature]);
			$result[$nature] = array_reverse($result[$nature]);
			foreach(array_reverse($to_add) as $code => $libelle){
				$result[$nature][$code] = $libelle;
			}
			$result[$nature] = array_reverse($result[$nature]);

		}
		return $result;
	}

	/**
	 * Ca devrait s'appeller getByNatureAndClassification...
	 */
    public function getAllByNature(){
        $result = array();
        foreach($this->getAll() as $type){
            $matiere1 = $type['code'][0];
            $matiere2 = $type['code'][1];
            $result[$type['nature_id']][$matiere1][$matiere2][$type['code']] =$type['libelle'];
        }
        return $result;
    }

    public function getAllByNatureMatiere1(){
        $result = array();
        foreach($this->getAll() as $type){
            if ($type['code'][1] == '0'){
                $matiere1 = $type['code'][0];
                $result[$type['nature_id']][$matiere1][$type['code']] = $type['libelle'];
            }
        }
        return $result;
    }

    public function getAllDefaultNature(){
        $result = array();
        foreach($this->getAll() as $type){
            if (substr($type['code'],0,2) == '99') {
                $result[$type['nature_id']][$type['code']] = $type['libelle'];
            }
        }
        return $result;
    }


    public function getLibelle($code){
        $sql = "SELECT libelle FROM actes_type_pj WHERE code=? LIMIT 1";
        return $this->queryOne($sql,$code);

    }

}