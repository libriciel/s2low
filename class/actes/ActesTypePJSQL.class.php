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
				if (mb_substr($code,0,3)=='99_'){
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
            if (mb_substr($type['code'],0,2) == '99') {
                $result[$type['nature_id']][$type['code']] = $type['libelle'];
            }
        }
        return $result;
    }


    public function getLibelle($code){
        $sql = "SELECT libelle FROM actes_type_pj WHERE code=? LIMIT 1";
        return $this->queryOne($sql,$code);
    }

    public function getDefaultType($nature_code){
		$correspondance_nature_type = array(
			'1'=> '99_DE',
			'2' => '99_AR',
			'3' => '99_AI',
			'4' => '99_DC',
			'5' => '99_BU',
			'6' => '99_AU',
		);
		if (empty($correspondance_nature_type[$nature_code])){
			return "99_AU";
		}
		return $correspondance_nature_type[$nature_code];
	}

    /**
     * @param $codePj
     * @param $natureCode
     * @return string
     */
    public function getTypeDocument(string $codePj, string $natureCode): string
    {
        $typeDocument = "Annexe";
        if ($codePj === $this->getDefaultType($natureCode)) {
            $typeDocument = "Document principal";
        }
        return $typeDocument;
    }

}