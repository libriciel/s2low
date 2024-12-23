<?php

namespace S2lowLegacy\Class\actes;

use PDO;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Model\ModuleSQL;

class ActesTransactionsSQL extends SQL
{
    public const MAX_ID = 2147483647; /* (signed) integer max size in PostgreSQL*/

    public const AUTHORITY_ID = 'authority_id';
    public const ENVELOPE_ID = 'envelope_id';

    public function getInfo($id)
    {
        $sql = "SELECT * FROM actes_transactions WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function getStatusInfo($id, $status)
    {
        $sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? AND status_id=?";
        return $this->queryOne($sql, $id, $status);
    }

    public function getStatusInfoWithFluxRetour($id, $status)
    {
        $sql = "SELECT flux_retour,transaction_id FROM actes_transactions_workflow WHERE transaction_id=? AND status_id=?";

        $pdo = $this->getSQLQuery()->getPdo();

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $status]);

        $flux_retour = null;
        $transaction_id = null;
        $stmt->bindColumn(1, $flux_retour, PDO::PARAM_LOB);
        $stmt->bindColumn(2, $transaction_id, PDO::PARAM_INT);
        $stmt->fetch(PDO::FETCH_BOUND);
        if ($flux_retour === null) {
            return ['flux_retour' => null, 'transaction_id' => null];
        }
        $flux_retour_contents = stream_get_contents($flux_retour);
        fclose($flux_retour);

        return ['flux_retour' => $flux_retour_contents, 'transaction_id' => $transaction_id];
    }

    public function getLastStatusInfo($id)
    {
        $sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? ORDER BY date DESC,id DESC LIMIT 1";
        return $this->queryOne($sql, $id);
    }


    public function getAllFile($id)
    {
        $sql = "SELECT * FROM actes_included_files WHERE transaction_id=? ORDER BY id";
        return $this->query($sql, $id);
    }

    public function setArchiveURL($transaction_id, $archive_url)
    {
        $sql = "UPDATE actes_transactions SET archive_url=? " .
            " WHERE id=?";
        $this->query($sql, $archive_url, $transaction_id);
    }


    public function updateStatus(
        int $transaction_id,
        int $status_id,
        ?string $message,
        string $flux_retour = '',
        ?string $date = null
    ): int {

        $message = mb_substr($message ?? '', 0, 512); // quickfix transition 8.0

        if ($date === null) {
            $date = date('Y-m-d H:i:s');
        }
        $sql = 'INSERT INTO actes_transactions_workflow (transaction_id, status_id, date, message ) ' .
            ' VALUES( ? , ? , ? , ? ) RETURNING ID';

        $id = $this->queryOne($sql, $transaction_id, $status_id, $date, $message);

        if (!empty($flux_retour)) {
            $sql = 'UPDATE actes_transactions_workflow SET flux_retour = ? WHERE id = ?';
            $pdo = $this->getSQLQuery()->getPdo();
            $stmt = $pdo->prepare($sql);                                    //QUICKFIX Passage UTF-8
            $stmt->bindParam(1, $flux_retour, PDO::PARAM_LOB);
            $stmt->bindParam(2, $id);
            $stmt->execute();
        }
        $sql = 'UPDATE actes_transactions SET last_status_id=? WHERE id=?';
        $this->query($sql, $status_id, $transaction_id);
        return $id;
    }

    public function getArchiveFStatus($status_id, $authority_id = 0)
    {
        $sql = 'SELECT  actes_transactions.id as id FROM actes_transactions ' .
            " WHERE last_status_id=? ";
        $data = [$status_id];
        if ($authority_id) {
            $sql .= " AND authority_id= ? ";
            $data[] = $authority_id;
        }
        $sql .= "ORDER BY id";
        return $this->query($sql, $data);
    }

    public function getArchiveFromStatusWithSAE($status_id)
    {
        $sql = "SELECT  *,actes_transactions.id as id FROM actes_transactions " .
            " JOIN authorities ON actes_transactions.authority_id=authorities.id " .
            " WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' ";
        return $this->query($sql, $status_id);
    }

    public function getEnveloppeIdByTransactionsStatus($last_status_id, $antivirus_check = true)
    {
        $sql = "SELECT DISTINCT envelope_id FROM actes_transactions WHERE last_status_id=? AND antivirus_check=?";
        return $this->queryOneCol($sql, $last_status_id, $antivirus_check);
    }

    public function getIdByEnvelopeId($envelope_id)
    {
        $sql = "SELECT id FROM actes_transactions WHERE envelope_id=?";
        return $this->queryOneCol($sql, $envelope_id);
    }


    public function getLastArchiveFromStatus($status_id, $start_date)
    {
        $sql = "SELECT  actes_transactions.*,authorities.*,actes_transactions.id as id FROM actes_transactions " .
            " JOIN authorities ON actes_transactions.authority_id=authorities.id " .
            " JOIN actes_transactions_workflow ON actes_transactions_workflow.transaction_id=actes_transactions.id" .
            " AND actes_transactions_workflow.status_id=? " .
            " WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL " .
            " AND authorities.pastell_url != '' AND actes_transactions_workflow.date > ?";
        return $this->query($sql, $status_id, $status_id, $start_date);
    }

    public function getByStatusSinceDate($status_id, $date_status)
    {
        $sql = "SELECT DISTINCT actes_transactions.id FROM actes_transactions " .
            " JOIN actes_transactions_workflow ON actes_transactions.id = actes_transactions_workflow.transaction_id AND actes_transactions.last_status_id=actes_transactions_workflow.status_id " .
            " WHERE last_status_id=? AND date<?";
        return $this->queryOneCol($sql, $status_id, $date_status);
    }


    public function getEnvelopeToDelete()
    {
        $sql = "SELECT  actes_envelopes.*,actes_transactions.id as transaction_id, actes_transactions.user_id " .
            " FROM actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id " .
            " WHERE last_status_id=13 OR last_status_id = 6";
        return $this->query($sql);
    }

    public function getRelatedTransaction($id)
    {
        $result = array();
        $sql = "SELECT * " .
            " FROM actes_transactions " .
            " WHERE related_transaction_id=?";
        foreach ($this->query($sql, $id) as $line) {
            $result[] = $line;
            $result = array_merge($result, $this->getRelatedTransaction($line['id']));
        }
        return $result;
    }

    public function setSAETransferIdentifier($id, $transfer_identifier)
    {
        $sql = "UPDATE actes_transactions SET sae_transfer_identifier=? WHERE id=?";
        $this->query($sql, $transfer_identifier, $id);
    }

    public function getDateTampon($id)
    {
        $sql = "SELECT actes_envelopes.submission_date, actes_transactions_workflow.date, actes_transactions.unique_id FROM actes_transactions, actes_envelopes, actes_transactions_workflow " .
            " WHERE actes_transactions.envelope_id = actes_envelopes.id " .
            " AND actes_transactions.id = ? " .
            " AND actes_transactions_workflow.transaction_id = actes_transactions.id " .
            " AND actes_transactions_workflow.status_id=?";
        return $this->queryOne($sql, $id, 4);
    }

    public function getTransactionForAntiVirus()
    {
        $sql = "SELECT DISTINCT id FROM actes_transactions WHERE last_status_id IN (1,2) AND antivirus_check=?";
        return $this->queryOneCol($sql, 0);
    }

    public function setAntivirusCheck($transaction_id)
    {
        $sql = "UPDATE actes_transactions SET antivirus_check=? WHERE id=?";
        $this->query($sql, 1, $transaction_id);
    }

    public function updateLastStatusId()
    {
        $sql = "SELECT id FROM actes_transactions";

        $id_list = $this->queryOneCol($sql);
        foreach ($id_list as $transaction_id) {
            //echo "$transaction_id : ";
            $status = $this->getlaststatusforid($transaction_id);
            //echo "$status\n";
            $sqlupdate = "update actes_transactions set last_status_id = ? where  id = ?";
            $this->query($sql, $status, $transaction_id);
        }
    }

    public function getlaststatusforid($idtrans)
    {
        $sql = " select status_id from actes_transactions_workflow where transaction_id = ? ORDER BY id DESC limit 1";
        $status = $this->queryOneCol($sql, $idtrans);
        return $status[0];
    }

    public function getLastTransactionWorkflowInfo($id)
    {
        $sql = " select * from actes_transactions_workflow where transaction_id = ? ORDER BY id DESC limit 1";
        return $this->queryOne($sql, $id);
    }

    /**
     * @param $dateStatusCible
     * @param int $status_origine
     * @param int $status_cible
     * @return array
     * Retourne le nombre de transactions passés de $status_origine à $status_cible depuis $dateStatusCible
     * Cette fonction peut être perturbée par les modifications manuelles de status.
     */
    public function getStatusTransitionStatistics($dateStatusCible, int $status_origine, int $status_cible): array
    {
        $sql = "
    SELECT 
  COUNT(atw_cible.transaction_id) AS nb_transactions_cible, 
  AVG(atw_cible.date - atw_origine.date) AS delai_de_transmission_moyen 
FROM 
  actes_transactions_workflow AS atw_cible
  INNER JOIN actes_transactions_workflow AS atw_origine ON atw_cible.transaction_id = atw_origine.transaction_id 
WHERE 
  atw_cible.date > ? 
  AND atw_cible.status_id = ? 
  AND atw_origine.status_id = ?";

        $results = $this->query($sql, $dateStatusCible, $status_origine, $status_cible);
        return [$results[0]["nb_transactions_cible"], $results[0]["delai_de_transmission_moyen"]];
    }

    /**
     * @param $date_status_cible
     * @return float
     */
    public function getNbPostesDepuis($date_status_cible): float
    {
        $sql = "SELECT COUNT(id) AS nb_post_par_min FROM actes_transactions_workflow WHERE date > ? AND status_id=1; ";

        $results = $this->query($sql, $date_status_cible);
        return $results[0]["nb_post_par_min"];
    }

    public function getNbTransactionByMonth()
    {
        $sql = "SELECT count(*) as nb,date_trunc('month', submission_date) as month  FROM actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id" .
            " WHERE actes_transactions.type = '1' AND submission_date>'2015-01-01'" .
            " GROUP BY month" .
            " ORDER BY month DESC";
        return $this->query($sql);
    }

    public function getBySirenAndNumeroInterne($siren, $numero_interne, TypeTransaction $type = TypeTransaction::TransmissionActe, $type_reponse_not_null = false)
    {
        $sql = "SELECT actes_transactions.id from actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE siren=? AND number=? AND type=? ";

        if ($type_reponse_not_null) {
            $sql .= " AND type_reponse IS NOT NULL ";
        }

        $sql .= " ORDER BY submission_date DESC";


        return $this->queryOne($sql, $siren, $numero_interne, $type->value);
    }

    public function getNbByStatus($status_id)
    {
        $sql = "SELECT count(id) FROM actes_transactions WHERE last_status_id=?";
        return $this->queryOne($sql, $status_id);
    }

    public function getLastDemandeClassificationTransmis($siren)
    {
        $sql = "SELECT actes_transactions.id FROM actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE siren=? AND type='7' AND last_status_id=? ORDER BY submission_date DESC";
        return $this->queryOne($sql, $siren, ActesStatusSQL::STATUS_TRANSMIS);
    }

    public function setUniqueID($acteID, $unique_id)
    {
        $sql = "UPDATE actes_transactions SET unique_id=? WHERE id=?";
        $this->query($sql, $unique_id, $acteID);
    }

    public function createRelatedTransaction($envelope_id, $type, $decision_date, $related_transaction_id)
    {
        $sql = "INSERT INTO actes_transactions (envelope_id,type,related_transaction_id," .
            " nature_code,nature_descr,title, subject, number,classification,classification_date,decision_date," .
            " unique_id, archive_url,broadcast_emails,broadcast_send_sources, broadcasted,user_id,authority_id) " .
            " SELECT ?,?,?,nature_code,nature_descr,title, subject, number,classification" .
            ",classification_date,?,unique_id, archive_url,broadcast_emails,broadcast_send_sources, broadcasted,user_id,authority_id " .
            " FROM actes_transactions where id = ? RETURNING id";
        return $this->queryOne($sql, $envelope_id, $type, $related_transaction_id, $decision_date, $related_transaction_id);
    }


    public function create($envelope_id, $status, $user_id, $authority_id)
    {
        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id) VALUES (?,?,?,?) RETURNING ID;";
        return $this->queryOne($sql, $envelope_id, $status, $user_id, $authority_id);
    }

    public function guessUniqueId($transaction_id)
    {
        //034-000000000-20170701-20170728C-AI

        $sql = "SELECT * FROM actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE actes_transactions.id=?";
        $info = $this->queryOne($sql, $transaction_id);

        $sql2 = "SELECT short_descr FROM actes_natures WHERE id=?";
        $nature = $this->queryOne($sql2, $info['nature_code']);


        if (!is_null($info['decision_date'])) {
            $datetemp = strtotime($info['decision_date']);
        } else {
            $datetemp = time();
        }

        return sprintf(
            "%s-%s-%s-%s-%s",
            $info['department'],
            $info['siren'],
            date("Ymd", $datetemp), #Quickfix passge php8
            $info['number'],
            $nature
        );
    }

    public function getNbByStatusAndDate($status_id, $submission_date_max)
    {
        $sql = "SELECT count(*) FROM actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE last_status_id=? AND submission_date <?";
        return $this->queryOne($sql, $status_id, $submission_date_max);
    }

    public function getTransactionToAutoBroadcast()
    {
        $sql = "SELECT actes_transactions.id  FROM actes_transactions " .
            " WHERE last_status_id IN ('-1','4','7','8','11','21')  AND auto_broadcasted=false AND type IN ('1','2','3','4','5','6')";
        return $this->queryOneCol($sql);
    }

    public function setBroadcasted($transactionId)
    {
        $sql = "UPDATE actes_transactions SET broadcasted = TRUE " .
            " WHERE actes_transactions.id = ?";
        $this->query($sql, $transactionId);
    }

    public function setAutoBroadcasted($transactionId)
    {
        $sql = "UPDATE actes_transactions SET auto_broadcasted = TRUE " .
            " WHERE actes_transactions.id = ?";
        $this->query($sql, $transactionId);
    }

    public function getNbByStatusAndAuthority($status_id, $authority_id)
    {
        $sql = "SELECT count(*) FROM actes_transactions " .
            " WHERE last_status_id=? AND authority_id = ?";
        return $this->queryOne($sql, $status_id, $authority_id);
    }

    public function getIdByStatusAndAuthority($status_id, $authority_id)
    {
        $sql = "SELECT id FROM actes_transactions " .
            " WHERE last_status_id=? AND authority_id = ? ORDER BY actes_transactions.id DESC ";
        return $this->queryOneCol($sql, $status_id, $authority_id);
    }

    public function getListByStatusAndAuthority(
        $status_id,
        $authority_id,
        $offset,
        $limit,
        ?string $min_submission_date = null,
        ?string $max_submission_date = null,
        ?int $type_acte
    ): array | false {
        $offset = intval($offset);
        $limit = intval($limit);
        $authority_id = intval($authority_id);
        $status_id = intval($status_id);

        $sql = "SELECT actes_transactions.id,subject,number,date(decision_date),nature_descr,classification,type FROM actes_transactions ";
        if ($min_submission_date !== null || $max_submission_date !== null) {
            $sql .= "JOIN actes_transactions_workflow ON transaction_id=actes_transactions.id";
        }
            $sql .= " WHERE last_status_id=? AND authority_id = ?";
        $data = [$status_id, $authority_id];
        if ($min_submission_date !== null) {
            $sql .= " AND date >= ? ";
            $data[] = $min_submission_date;
        }
        if ($max_submission_date !== null) {
            $sql .= " AND date <= ? ";
            $data[] = $max_submission_date;
        }
        if ($min_submission_date !== null || $max_submission_date !== null) {
            $sql .= "AND status_id = ?";
            $data[] = $status_id;
        }
        if ($type_acte !== null) {
            $sql .= "AND status_id = ?";
            $data[] = $status_id;
        }


        $sql .= " ORDER BY actes_transactions.id DESC OFFSET $offset LIMIT $limit";
        return $this->query($sql, $data);
    }

    public function listDocumentPrefectureNonLu($authority_id)
    {
        $sql = "SELECT id,type,related_transaction_id,number,unique_id,last_status_id FROM actes_transactions " .
            " WHERE authority_id=? AND type IN ('2','3','4','5') AND type_reponse IS NULL AND lu=false" .
            " ORDER BY id";
        return $this->query($sql, $authority_id);
    }

    public function markAsRead($authority_id, $transaction_id)
    {
        $sql = "UPDATE actes_transactions SET lu=true WHERE id=? AND authority_id=?";
        $this->query($sql, $transaction_id, $authority_id);
    }

    public function getTransactionToArchive($nb_days = 62, $authority_id = 0, $is_auto = true)
    {
        $date = date('Y-m-d', strtotime("- $nb_days DAY"));

        $sql = "SELECT at.id FROM actes_transactions AS at " .
            " JOIN actes_transactions_workflow AS atw ON (atw.transaction_id = at.id AND atw.status_id= 4) " .
            " JOIN authorities ON authorities.id=at.authority_id " .
            " JOIN authority_pastell_config ON authority_pastell_config.authority_id=authorities.id " .
            " AND at.id >= authority_pastell_config.transaction_id_min " .
            " AND at.id <= authority_pastell_config.transaction_id_max" .
            " WHERE authority_pastell_config.module_id = 1 " .
            " AND at.type='1' " .
            " AND at.last_status_id IN (4,5) " .
            " AND atw.date > '2008-06-01' " .
            " AND atw.date < ? ";

        $data[] = $date;

        if ($is_auto) {
            $sql .= " AND authority_pastell_config.is_auto='t' ";
        }

        if ($authority_id) {
            $sql .= "AND at.authority_id=? ";
            $data[] = $authority_id;
        }

        $sql .= " ORDER BY at.id ";
        return $this->queryOneCol($sql, $data);
    }

    public function getTransactionToSendSAEWithLimit($limit)
    {
        $sql = "SELECT temp.id FROM (
                    SELECT at.id,
                    at.last_status_id,
                    ROW_NUMBER () OVER (PARTITION BY at.authority_id ORDER BY CASE
                        WHEN at.last_status_id IN(12,20,14) THEN 1
                        ELSE 2
                    END,
                    at.id) AS Rank
                    FROM actes_transactions AS at
                    JOIN authorities ON authorities.id=at.authority_id
                    JOIN authority_pastell_config ON authority_pastell_config.authority_id=authorities.id
                    WHERE authority_pastell_config.module_id = 1
                    AND authority_pastell_config.is_auto='t'
                    AND at.last_status_id IN (12,20,14,19)
                    ORDER BY at.id ) AS temp 
                WHERE Rank <= ?
                    AND last_status_id=19";

        return $this->queryOneCol($sql, $limit);
    }

    public function getTransactionToSendSAE($status_id = ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE, $is_auto = true)
    {
        $sql = "SELECT at.id FROM actes_transactions AS at " .
            " JOIN authorities ON authorities.id=at.authority_id " .
            " JOIN authority_pastell_config ON authority_pastell_config.authority_id=authorities.id " .
            " WHERE authority_pastell_config.module_id = 1 ";

        if ($is_auto) {
            $sql .= " AND authority_pastell_config.is_auto='t' ";
        }

        $sql .= " AND at.last_status_id = ?  " .
            " ORDER BY at.id ";

        return $this->queryOneCol(
            $sql,
            $status_id
        );
    }

    public function getAllForExport($authority_id, $min_transaction_id = 0, $max_transaction_id = self::MAX_ID)
    {
        $sql = "SELECT actes_transactions.id, unique_id,file_path,file_size, actes_envelopes.id as idEnveloppe FROM actes_transactions " .
            " JOIN actes_envelopes on actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE actes_transactions.authority_id=? AND actes_transactions.id>=? AND actes_transactions.id<=?";
        return $this->query($sql, $authority_id, $min_transaction_id, $max_transaction_id);
    }

    public function getNbTransactionGroupBySAEStatusForModeAuto(array $status_list)
    {
        $result = [];
        $module_id = $this->queryOne(
            "SELECT id FROM modules WHERE name=?",
            ModuleSQL::ACTES_MODULE_NAME
        );
        $sql = "SELECT authority_pastell_config.authority_id,authorities.name,last_status_id,count(last_status_id) FROM authority_pastell_config " .
            " JOIN actes_transactions ON actes_transactions.authority_id=authority_pastell_config.authority_id " .
            " JOIN authorities ON authority_pastell_config.authority_id=authorities.id " .
            " WHERE is_auto=true and module_id=$module_id AND last_status_id IN (" . implode(',', $status_list) . ") " .
            " GROUP BY authority_pastell_config.authority_id,authorities.name,last_status_id" .
            " ORDER BY authorities.name";
        foreach ($this->query($sql) as $line) {
            if (empty($result[$line['authority_id']])) {
                $result[$line['authority_id']] = [
                    'name' => $line['name'],
                    'status' => []
                ];
            }
            $result[$line['authority_id']]['status'][$line['last_status_id']] = $line['count'];
        }

        return $result;
    }

    public function getNbActesByAuthorityGroupIdBeetweenDate(
        int $authority_group_id,
        string $min_date,
        string $max_date
    ): array {
        $result = [];
        $sql = "SELECT authorities.id,authorities.name FROM authorities " .
            " WHERE authority_group_id=? ORDER BY authorities.name";
        $authorities_list = $this->query($sql, $authority_group_id);

        foreach ($authorities_list as $authority_info) {
            $result[$authority_info['id']] = [
                'authority_id' => $authority_info['id'],
                'name' => $authority_info['name'],
                'nb_transactions' => 0
            ];
        }

        $sql = "SELECT authorities.id, authorities.name, COUNT(actes_transactions) AS nb_transactions 
        FROM authorities 
        INNER JOIN actes_transactions ON actes_transactions.authority_id = authorities.id 
        WHERE authorities.authority_group_id = ? 
        AND actes_transactions.decision_date >= ?
        AND actes_transactions.decision_date <= ?
        GROUP BY authorities.id, authorities.name 
        ORDER BY authorities.name";
        $count = $this->query($sql, $authority_group_id, $min_date, $max_date);

        foreach ($count as $count_info) {
            $result[$count_info['id']]['nb_transactions'] = $count_info['nb_transactions'];
        }
        return array_values($result);
    }

    public function acteAlreadyHadStatut(mixed $transactionId, int $status): bool
    {
        $sql = 'SELECT id from actes_transactions_workflow where transaction_id=? and status_id=?';

        return $this->queryOne($sql, $transactionId, $status) !== false;
    }
}
