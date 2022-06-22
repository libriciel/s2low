<?php

class ActesClassificationCodesSQL extends SQL
{
    public function getDescription($authority_id, array $classification)
    {
        return $this->getDescriptionRecursif($authority_id, 1, $classification);
    }

    private function getDescriptionRecursif($authority_id, $level, array $classification, $parent_id = false)
    {

        if (empty($classification[$level - 1])) {
            return false;
        }
        $sql = "SELECT * FROM actes_classification_codes WHERE authority_id=? AND level=? AND code=?";

        $data = array($authority_id,$level,$classification[$level - 1]);
        if ($parent_id) {
            $sql .= " AND parent_id=?";
            $data[] = $parent_id;
        }

        $p1 = $this->queryOne($sql, $data);
        if (! $p1) {
            return false;
        }
        $data = $this->getDescriptionRecursif($authority_id, $level + 1, $classification, $p1['id']);
        if (! $data) {
            return $p1['description'];
        }
        return $data;
    }

    public function getAllDescription($authority_id)
    {
        $sql = "SELECT * FROM actes_classification_codes WHERE authority_id=? ORDER BY level,code";
        $result = array();
        foreach ($this->query($sql, $authority_id) as $line) {
            $result[$line['id']] = $line;
        }

        $result = $this->getAllChildrenRecur($result, '');


        return $result;
    }

    private function getAllChildrenRecur($result, $parent_id)
    {
        $return = $this->getAllChildren($result, $parent_id);
        foreach ($return as $id => $children) {
            $return[$id]['children'] = $this->getAllChildrenRecur($result, $id);
        }
        return $return;
    }

    private function getAllChildren($result, $parent_id)
    {
        $return = array();
        foreach ($result as $line) {
            if ($line['parent_id'] == $parent_id) {
                $return[$line['id']] = $line;
            }
        }

        return $return;
    }
}
