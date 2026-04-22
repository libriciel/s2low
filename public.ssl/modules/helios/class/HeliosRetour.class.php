<?php

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;

class HeliosRetour extends DataObject
{
    protected $objectName = "helios_retour";

    protected $dbFields = array (
    "siren" => array (
      "descr" => "No. de SIREN",
      "type" => "isString",
      "mandatory" => true
    ),
    "filename" => array (
      "descr" => "Nom du fichier",
      "type" => "isString",
      "mandatory" => true
    ),
    "date" => array (
      "descr" => "date de réception",
      "type" => "isDate",
      "mandatory" => true
    ),
    "status" => array (
      "descr" => "l'etat d'information",
      "type" => "isInt",
      "mandatory" => true
    )
    );
    public function getDocumentList($cond = "")
    {
        $tmp = "";
        if (!$this->pagerInit('DISTINCT *', ' helios_retour', $cond)) {
          //,$cond)) { //AICI FILTRUL
          //DISTINCT helios_transactions.id, helios_transactions.user_id, users.name, helios_transactions.filename, helios_transactions_workflow.transaction_id, helios_transactions_workflow.status_id, helios_transactions_workflow.date, helios_transactions_workflow.message ',

          //'DISTINCT actes_envelopes.id, actes_envelopes.user_id, actes_envelopes.siren, actes_envelopes.submission_date, actes_envelopes.department, actes_envelopes.district, actes_envelopes.authority_type_code, actes_envelopes.name, actes_envelopes.telephone, actes_envelopes.email, actes_envelopes.file_path, actes_envelopes.return_mail, actes_envelopes.file_size',
          // 'actes_envelopes LEFT JOIN actes_transactions ON actes_envelopes.id=actes_transactions.envelope_id LEFT JOIN users ON actes_envelopes.user_id=users.id', $cond)) {
            return false;
        }

        return $this->data;
    }

    public function changeStatus($id, $status)
    {
        $sql = "UPDATE helios_retour SET status = ? WHERE id = ?;";
        if (! $this->db->exec($sql, [$status,$id])) {
            $this->errorMsg = "Erreur lors du changement d'état.";
            return false;
        }
        return true;
    }
    public function sendfile($fileName)
    {
        $fileName = trim($fileName);
        if (!file_exists(HELIOS_RESPONSES_ROOT . "/" . $fileName)) {
            $this->errorMsg = "Le fichier '" . $fileName . "' n'est pas/plus disponible.";
            echo "<br>helios Tansaction_class: sendFile " . $this->errorMsg;
            return false;
        }

        $ret_value = true;
      //AICI pot incerca sa modific parametrii...
        if (!\S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser(HELIOS_RESPONSES_ROOT . "/" . $fileName, $fileName, "text/xml")) {
            $this->errorMsg = "Erreur envoi fichier";
            echo "<br> heliosTansaction_class: sendFile " . $fileName . " :" . $this->errorMsg;
            $ret_value = false;
        }
        return $ret_value;
    }

    public static function getRetourHistory()
    {

        $sql = "SELECT hr.filename, hr.date, hr.siren, hr.file_size, hr.sha1";
        $sql .= " FROM helios_retour hr";

        $sql .= " ORDER BY hr.date DESC";

        $db = DatabasePool::getInstance();

        $result = $db->select($sql);

        $trans = array();

        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $trans[] = $row;
            }
        }
        return $trans;
    }
    public function getRetourList($cond = "")
    {
        $sql = "SELECT filename, date, id";
        $sql .= " FROM helios_retour  " . $cond;
        $sql .= " ORDER BY date DESC";
        $db = DatabasePool::getInstance();
        $result = $db->select($sql);
        $trans = array();
        if (! $result->isError()) {
            while ($row = $result->get_next_row()) {
                $trans[] = $row;
            }
        }
        return $trans;
    }
}
