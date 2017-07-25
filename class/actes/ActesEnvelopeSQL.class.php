<?php 


class ActesEnvelopeSQL extends SQL
{

    public function getInfo($id)
    {
        $sql = "SELECT * FROM actes_envelopes WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function findByAnomalieEnveloppeName($anomalie_enveloppe_name)
    {
        $file_path = "%" . substr($anomalie_enveloppe_name, 4, -3) . "%";
        $sql = "SELECT id FROM actes_envelopes  WHERE file_path LIKE ? ";
        return $this->queryOne($sql, $file_path);
    }

    public function create($user_id, $file_path)
    {
        $sql = "INSERT INTO actes_envelopes(user_id,file_path) VALUES(?,?) RETURNING ID";
        return $this->getSQLQuery()->queryOne($sql, $user_id, $file_path);
    }

    public function createRelatedEnveloppe($envelope_id,$file_path, $file_size)  {
        $sql = "INSERT INTO actes_envelopes(user_id,submission_date,siren,department,district," .
                "authority_type_code,name,telephone,email,file_path,file_size,return_mail)" .
                " SELECT user_id,now(), siren,department,district,authority_type_code,name,telephone," .
                " email,?,?,return_mail FROM actes_envelopes WHERE id= ? RETURNING id";
        return $this->queryOne($sql,$file_path,$file_size,$envelope_id);
    }

    public function getLastEnvelope(){
        $sql = "SELECT * FROM actes_envelopes ORDER BY id DESC LIMIT 1";
        return $this->queryOne($sql);
    }

}