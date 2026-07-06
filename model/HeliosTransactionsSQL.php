<?php

namespace S2lowLegacy\Model;

use phpseclib3\Exception\FileNotFoundException;
use S2low\Services\FileDataProvider;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Lib\SQL;

class HeliosTransactionsSQL extends SQL implements FileDataProvider
{
    public const ERREUR = -1;
    public const ANNULE = 0;
    public const POSTE = 1;
    public const ATTENTE = 2;
    public const TRANSMIS = 3;
    public const ACQUITTE = 4;
    public const VALIDE = 5;
    public const REFUSE = 6;
    public const EN_TRAITEMENT = 7;
    public const INFORMATION_DISPONIBLE = 8;

    public const ENVOYE_AU_SAE = 9;
    public const ACCEPTE_SAE = 10;
    public const REFUSE_SAE = 11;
    public const ATTENTE_POSTEE = 14;
    public const ATTENTE_SIGNEE = 13;

    public const STATUS_EN_ATTENTE_TRANMISSION_SAE = 19;
    public const STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = 20;

    public const DETRUITE = 22;

    public const TRANSMIS_SANS_ACK = 24;

    private const SEND_WARNING_AFTER_SECOND = 172800;

    private const WORKFLOW_MESSAGE_MAX_LENGTH = 512;

    public const MAX_ID = 2147483647; /* (signed) integer max size in PostgreSQL*/

    public const AUTHORITY_ID = 'authority_id';

    public function create($filename, $sha1, $user_id, $authority_id, $file_size, $siren)
    {
        $sql = "INSERT INTO helios_transactions(user_id, filename, file_size, siren, sha1, submission_date, authority_id) " .
                " VALUES (?,?,?,?,?,now(),?) RETURNING ID;";
        return $this->queryOne($sql, $user_id, $filename, $file_size, $siren, $sha1, $authority_id);
    }

    public function getInfo($id)
    {
        $sql = "SELECT * FROM helios_transactions WHERE id=?";
        return $this->queryOne($sql, (int) $id);
    }

    public function updateStatus($transaction_id, $status_id, $message)
    {
        if (mb_strlen($message) > self::WORKFLOW_MESSAGE_MAX_LENGTH) {
            $message =  mb_substr($message, 0, self::WORKFLOW_MESSAGE_MAX_LENGTH);
        }
        $date = date("Y-m-d H:i:s");

        $sql = "INSERT INTO helios_transactions_workflow (transaction_id, status_id, date, message) " .
                " VALUES( ? , ? , ? , ? ) RETURNING ID ";
        $id = $this->queryOne($sql, $transaction_id, $status_id, $date, $message);
        $sql = "UPDATE helios_transactions SET last_status_id=? " .
                " WHERE id=?";
        $this->query($sql, $status_id, $transaction_id);
        return $id;
    }

    public function getLastStatusInfo($id)
    {
        $sql = "SELECT * FROM helios_transactions_workflow WHERE transaction_id=? ORDER BY date DESC,id DESC LIMIT 1";
        return $this->queryOne($sql, $id);
    }

    public function setSAETransferIdentifier($transaction_id, $transfer_identifier)
    {
        $sql = "UPDATE helios_transactions SET sae_transfer_identifier=? WHERE id=?";
        $this->query($sql, $transfer_identifier, $transaction_id);
    }

    /** @deprecated 4.0.4 */
    public function getArchiveFromStatusWithSAE($status_id, $date)
    {
        $sql = "SELECT  *,helios_transactions.id as id FROM helios_transactions " .
                " JOIN users ON helios_transactions.user_id = users.id " .
                " JOIN authorities ON users.authority_id=authorities.id " .
                " JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id=helios_transactions.id" .
                " AND helios_transactions_workflow.status_id=? " .
                " WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' " .
                " AND helios_transactions_workflow.date>?";
        return $this->query($sql, $status_id, $status_id, $date);
    }

    public function getIdFromStatusWithSAE($status_id, $date)
    {
        $sql = "SELECT  helios_transactions.id as id FROM helios_transactions " .
            " JOIN users ON helios_transactions.user_id = users.id " .
            " JOIN authorities ON users.authority_id=authorities.id " .
            " JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id=helios_transactions.id" .
            " AND helios_transactions_workflow.status_id=? " .
            " WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' " .
            " AND helios_transactions_workflow.date>?";
        return $this->queryOneCol($sql, $status_id, $status_id, $date);
    }

    public function setArchiveURL($transaction_id, $archive_url)
    {
        $sql = "UPDATE helios_transactions SET archive_url=? " .
                " WHERE id=?";
        $this->query($sql, $archive_url, $transaction_id);
    }

    public function getTransactionToDelete()    //TODO : il semblerait que ce ne soit pas utilisé ?
    {
        $sql = "SELECT * FROM helios_transactions WHERE last_status_id=10 OR last_status_id=6";
        return $this->query($sql);
    }

    public function getTransactionsADetruire($date)
    {
        $sql = 'SELECT helios_transactions.id FROM helios_transactions_workflow 
    JOIN helios_transactions ON helios_transactions_workflow.transaction_id=helios_transactions.id 
                                    AND helios_transactions_workflow.status_id = helios_transactions.last_status_id 
    WHERE helios_transactions_workflow.date < ? AND helios_transactions_workflow.status_id IN (?,?) ';

        return $this->queryOneCol(
            $sql,
            $date,
            HeliosTransactionsSQL::INFORMATION_DISPONIBLE,
            HeliosTransactionsSQL::ERREUR
        );
    }

    public function updateLastStatusId()
    {
        $sql2 = "UPDATE helios_transactions SET last_status_id = ? WHERE id=?";
        $sql = "SELECT id FROM helios_transactions WHERE last_status_id IS NULL";

        $id_list = $this->queryOneCol($sql);
        foreach ($id_list as $transaction_id) {
            $last_status_id = $this->getLatestStatusId($transaction_id);
            $this->query($sql2, $last_status_id, $transaction_id);
            echo "$transaction_id : $last_status_id\n";
        }
    }

    public function getLatestStatusId($id)
    {
        $sql = "SELECT status_id " .
                " FROM helios_transactions_workflow " .
                " WHERE transaction_id=? " .
                " ORDER BY date DESC, id DESC LIMIT 1";
        return $this->queryOne($sql, $id);
    }

    public function setLastStatusId($transaction_id)
    {
        $last_status_id = $this->getLatestStatusId($transaction_id);
        $sql = "UPDATE helios_transactions SET last_status_id = ? WHERE id=?";
        $this->query($sql, $last_status_id, $transaction_id);
    }

    public function getIdsByStatus($status_id, $authority_id = 0)
    {
        $sql = "SELECT  id FROM helios_transactions " .
                " WHERE last_status_id=? ";
        $data = [$status_id];
        if ($authority_id) {
            $sql .= " AND authority_id= ? ";
            $data[] = $authority_id;
        }

        $sql .= " ORDER BY id";
        return $this->queryOneCol($sql, $data);
    }

    public function getIdsByStatusAndPasstrans($status_id, bool $helios_use_passtrans)
    {
        $sql = "SELECT  helios_transactions.id FROM helios_transactions " .
            " JOIN authorities ON authorities.id=helios_transactions.authority_id " .
            " WHERE last_status_id=? AND  helios_use_passtrans =?";

        $sql .= " ORDER BY id";
        return $this->queryOneCol($sql, [$status_id,$helios_use_passtrans ? "true" : "false"]);
    }

    public function getIdByNomFicAndCodCol($nomFic, $cod_col)
    {
        $sql = "SELECT id FROM helios_transactions WHERE xml_nomfic = ? AND xml_cod_col=?";
        return $this->queryOneCol($sql, $nomFic, $cod_col);
    }

    public function getIdByNomFic($nomFic)
    {
        $sql = "SELECT id FROM helios_transactions WHERE xml_nomfic = ? ";
        return $this->queryOneCol($sql, $nomFic);
    }


    public function nomFicExists($nom_fic, $cod_col)
    {
        $sql = "SELECT count(*) FROM helios_transactions WHERE xml_nomfic= ? AND xml_cod_col=?";
        return $this->queryOne($sql, $nom_fic, $cod_col);
    }

    public function setNomFic($transaction_id, $nom_fic)
    {
        $sql = "UPDATE helios_transactions SET xml_nomfic=? WHERE id=?";
        $this->query($sql, $nom_fic, $transaction_id);
    }

    public function setInfoFromPESAller($transaction_id, array $info)
    {
        $sql = "UPDATE helios_transactions SET xml_nomfic=?, xml_cod_col=?, xml_cod_bud=?, xml_id_post=? WHERE id=?";
        $this->query($sql, $info['nom_fic'], $info['cod_col'], $info['cod_bud'], $info['id_post'], $transaction_id);
    }

    public function setCompleteName($transaction_id, $completeName)
    {
        $sql = "UPDATE helios_transactions SET complete_name = ? WHERE id= ?";
        $this->query($sql, $completeName, $transaction_id);
    }

    public function mustSendWarning($transaction_id)
    {
        $sql =  "SELECT submission_date, user_id " .
                    " FROM helios_transactions " .
                    " WHERE id = ? " .
                    " AND warning_sent IS NULL" .
                    " AND date_part('epoch', now()) - date_part('epoch', submission_date) > ? ";
        return $this->queryOne($sql, $transaction_id, self::SEND_WARNING_AFTER_SECOND);
    }

    public function setSendWarning($transaction_id)
    {
        $sql = "UPDATE helios_transactions SET warning_sent = 1 WHERE id = ?";
        $this->query($sql, $transaction_id);
    }

    public function delete($id)
    {
        $sql  = "DELETE FROM helios_transactions_workflow WHERE transaction_id=?";
        $this->query($sql, $id);
        $sql = "DELETE FROM helios_transactions WHERE id=?";
        $this->query($sql, $id);
    }

    public function setAcquitFilename($id, $acquit_filename)
    {
        $sql = "UPDATE helios_transactions SET acquit_filename =  ? WHERE id = ?";
        $this->query($sql, $acquit_filename, $id);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM helios_transactions ORDER BY id";
        return $this->query($sql);
    }

    public function getWorkflow($transaction_id)
    {
        $sql = "SELECT * FROM helios_transactions_workflow WHERE transaction_id=? ORDER BY date";
        return $this->query($sql, $transaction_id);
    }

    public function isDuplicate($sha1)
    {
        $sql = "select count(id) FROM helios_transactions WHERE sha1=?";
        return $this->queryOne($sql, $sha1);
    }

    public function getAllId($min_id = 0)
    {
        $sql = "SELECT id FROM helios_transactions WHERE id > ? ORDER BY id";
        return $this->queryOneCol($sql, $min_id);
    }

    public function setSignatureTechnique($transaction_id, $new_sha1, $new_file, $signature_technique = true)
    {
        $sql = "UPDATE helios_transactions SET sha1=?, file_size=?,signature_technique=? WHERE id=?";
        $this->query($sql, $new_sha1, $new_file, $signature_technique, $transaction_id);
    }

    public function getNbByStatus($status_id)
    {
        $sql = "SELECT count(*) FROM helios_transactions WHERE last_status_id=?";
        return $this->queryOne($sql, $status_id);
    }

    public function getNbByStatusAndDate($status_id, $submission_date_max)
    {
        $sql = "SELECT count(*) FROM helios_transactions WHERE last_status_id=? AND submission_date<?";
        return $this->queryOne($sql, $status_id, $submission_date_max);
    }

    public function getNonAcquitte()
    {
        $sql = "SELECT helios_transactions.id, helios_transactions.filename, xml_nomfic,helios_transactions.submission_date,helios_ftp_dest,xml_id_post,xml_cod_bud,xml_cod_col FROM helios_transactions " .
            " JOIN authorities ON authorities.id=helios_transactions.authority_id " .
            " WHERE last_status_id=3 AND helios_transactions.submission_date < ? " .
            " ORDER BY submission_date DESC ";

        $today = date("Y-m-d");
        return $this->query($sql, $today);
    }

    public function getNonAcquitteWithPasstransStatus(bool $usePasstrans, string $dateToday)
    {
        $sql = "SELECT helios_transactions.id, helios_transactions.filename, xml_nomfic,helios_transactions.submission_date,helios_ftp_dest,xml_id_post,xml_cod_bud,xml_cod_col,sha1 FROM helios_transactions " .
            " JOIN authorities ON authorities.id=helios_transactions.authority_id " .
            " WHERE last_status_id=3 AND helios_transactions.submission_date < ? " .
            " AND helios_use_passtrans = ? " .
            " ORDER BY submission_date DESC ";

        return $this->query($sql, $dateToday, intval($usePasstrans));
    }

    public function getNbTransactionByMonth()
    {
        $sql = "SELECT count(*) as nb,date_trunc('month', submission_date) as month  FROM helios_transactions " .
            " WHERE submission_date >= '2015-01-01'" .
            " GROUP BY month" .
            " ORDER BY month DESC";
        return $this->query($sql);
    }

    public function getNextTransactionToSendInCloud()
    {
        $sql = "SELECT id,sha1,filename FROM helios_transactions WHERE is_in_cloud=FALSE ORDER BY id ASC LIMIT 1";
        return $this->queryOne($sql);
    }

    public function setTransactionInCloud($id, bool $isInCloud = true)
    {
        $sql = "UPDATE helios_transactions SET is_in_cloud=? WHERE id=?";
        $this->query($sql, (int) $isInCloud, $id);
    }

    public function setTransactionInCloudRemove($id)
    {
        $sql = "UPDATE helios_transactions SET is_in_cloud=FALSE WHERE id=?";
        $this->query($sql, $id);
    }

    public function getAllTransactionToSendInCloud()
    {
        $sql = "SELECT id,sha1,filename FROM helios_transactions WHERE is_in_cloud=FALSE AND not_available=FALSE ORDER BY id ASC";
        return $this->query($sql);
    }

    public function getAllTransactionIdToSendInCloud()
    {
        $sql = "SELECT id FROM helios_transactions WHERE is_in_cloud=FALSE AND not_available=FALSE ORDER BY id ASC";
        return $this->queryOneCol($sql);
    }

    public function setTransactionNotAvailable($transaction_id)
    {
        $sql = "UPDATE helios_transactions SET not_available=? WHERE id=?";
        $this->query($sql, true, $transaction_id);
    }

    public function getAllIdPESAcquitToSendInCloud()
    {
        $sql = "SELECT id FROM helios_transactions WHERE pes_acquit_is_in_cloud=FALSE AND pes_acquit_not_available=FALSE ORDER BY id";
        return $this->queryOneCol($sql);
    }

    public function setPesAcquitNotAvailable($transaction_id)
    {
        $sql = "UPDATE helios_transactions SET pes_acquit_not_available=? WHERE id=?";
        $this->query($sql, true, $transaction_id);
    }

    public function setPesAcquitInCloud($id, bool $isInCloud)
    {
        $sql = "UPDATE helios_transactions SET pes_acquit_is_in_cloud=? WHERE id=?";
        $this->query($sql, intval($isInCloud), $id);
    }

    public function getAllForExport($authority_id, $min_transaction_id, $max_trasaction_id)
    {
        $sql = "SELECT id,sha1,filename,acquit_filename FROM helios_transactions WHERE authority_id=? AND id >= ? AND id<=? ORDER BY id";
        return $this->query($sql, $authority_id, $min_transaction_id, $max_trasaction_id);
    }

    public function getTransactionToPrepareToSAE($nb_days = 15, $authority_id = 0, $is_auto = true, array $status = [HeliosTransactionsSQL::INFORMATION_DISPONIBLE])
    {

        $status_list = implode(",", $status);

        $module_id = $this->queryOne("SELECT id FROM modules WHERE name=?", "helios");

        $date = date('Y-m-d', strtotime("-$nb_days days"));
        $sql = "SELECT DISTINCT(helios_transactions.id) FROM helios_transactions " .
            " JOIN helios_transactions_workflow " .
            " ON (helios_transactions.id = helios_transactions_workflow.transaction_id AND helios_transactions_workflow.status_id = 8) " .
            " JOIN authorities ON authorities.id=helios_transactions.authority_id " .
            " JOIN authority_pastell_config ON authority_pastell_config.authority_id=authorities.id " .
            " AND helios_transactions.id >= authority_pastell_config.transaction_id_min " .
            " AND helios_transactions.id <= authority_pastell_config.transaction_id_max " .
            " WHERE authority_pastell_config.module_id = $module_id " .
            " AND helios_transactions.last_status_id IN ($status_list)" .
            " AND helios_transactions_workflow.date <= ? ";

        $data = [
            $date
        ];

        if ($authority_id) {
            $sql .= "AND helios_transactions.authority_id=? ";
            $data[] = $authority_id;
        }
        if ($is_auto) {
            $sql .= "AND authority_pastell_config.is_auto='t'";
        }

        $sql .=  " ORDER BY helios_transactions.id ";
        return $this->queryOneCol($sql, $data);
    }

    public function getTransactionsToSendToSAE($limit = 100, $is_auto = true)
    {

        $blockingStatuses = [
            HeliosTransactionsSQL::ENVOYE_AU_SAE,
            HeliosTransactionsSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            HeliosTransactionsSQL::REFUSE_SAE
        ];

        $statusToSend = HeliosTransactionsSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE;

        $statusesToConsider = array_merge($blockingStatuses, [$statusToSend]);

        $blockingStatusesFormatted = implode(",", $blockingStatuses);
        $statusesToConsiderFormatted = implode(",", $statusesToConsider);

        $module_id = $this->queryOne("SELECT id FROM modules WHERE name=?", "helios");

        $sql = "SELECT id FROM (
                    SELECT 
                        DISTINCT(helios_transactions.id),
               ROW_NUMBER () OVER (PARTITION BY helios_transactions.authority_id ORDER BY CASE
                        WHEN helios_transactions.last_status_id IN($blockingStatusesFormatted) THEN 1
                        ELSE 2
                    END,
                    helios_transactions.id) AS Rank,
                    last_status_id
                    FROM helios_transactions
            JOIN authorities ON authorities.id=helios_transactions.authority_id
            JOIN authority_pastell_config ON authority_pastell_config.authority_id=authorities.id
            AND helios_transactions.id >= authority_pastell_config.transaction_id_min
            AND helios_transactions.id <= authority_pastell_config.transaction_id_max
            WHERE authority_pastell_config.module_id = $module_id
            AND helios_transactions.last_status_id IN ($statusesToConsiderFormatted)";

        if ($is_auto) {
            $sql .= "AND authority_pastell_config.is_auto='t'";
        }

        $sql .=  " ORDER BY helios_transactions.id ) AS Temp WHERE Rank <= ? AND last_status_id = $statusToSend";
        return $this->queryOneCol($sql, $limit);
    }

    public function getNbByStatusAndAuthority($status_id, $authority_id)
    {
        $sql = "SELECT count(*) FROM helios_transactions " .
            " WHERE last_status_id=? AND authority_id = ?";
        return $this->queryOne($sql, $status_id, $authority_id);
    }

    public function getNbTransactionGroupBySAEStatusForModeAuto(array $status_list)
    {
        $module_id = $this->queryOne(
            "SELECT id FROM modules WHERE name=?",
            ModuleSQL::HELIOS_MODULE_NAME
        );
        $sql = "SELECT authority_pastell_config.authority_id,authorities.name,last_status_id,count(last_status_id) FROM authority_pastell_config " .
            " JOIN helios_transactions ON helios_transactions.authority_id=authority_pastell_config.authority_id " .
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

    public function getIdBySHA1(string $sha1)
    {
        $sql = "select id from helios_transactions where sha1=?";
        return $this->queryOne($sql, $sha1);
    }

    public function isTransactionInCloud(int $object_id): bool
    {
        $sql = "SELECT is_in_cloud FROM helios_transactions WHERE id=?";
        return  $this->queryOne($sql, $object_id);
    }

    public function isTransactionAvailable(int $object_id): bool
    {
        $sql = "SELECT not_available FROM helios_transactions WHERE id=?";
        return ! $this->queryOne($sql, $object_id);
    }

    public function setTransactionAvailable(int $object_id, bool $available)
    {
        $sql = "UPDATE helios_transactions SET not_available=? WHERE id=?";
        $this->query($sql, intval(! $available), $object_id);
    }

    public function isPesAcquitAvailable(int $object_id): bool
    {
        $sql = "SELECT pes_acquit_not_available FROM helios_transactions WHERE id=?";
        return ! $this->queryOne($sql, $object_id);
    }

    public function setPesAcquitAvailable(int $object_id, bool $available)
    {
        $sql = "UPDATE helios_transactions SET pes_acquit_not_available=? WHERE id=?";
        $this->query($sql, intval(! $available), $object_id);
    }

    public function getByPesAcquitName(string $pes_aquit_filename)
    {
        $sql = "SELECT id FROM helios_transactions WHERE acquit_filename=?";
        return $this->queryOne($sql, $pes_aquit_filename);
    }

    public function getNbPesAllerByAuthorityGroupIdBetweenDate(
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

        $sql = "SELECT authorities.id,authorities.name, COUNT(helios_transactions) AS nb_transactions FROM authorities " .
            " INNER JOIN helios_transactions ON helios_transactions.authority_id = authorities.id " .
            " WHERE  authorities.authority_group_id =  ? " .
            " AND helios_transactions.submission_date >= ? " .
            " AND helios_transactions.submission_date <= ? " .
            " GROUP BY authorities.id,authorities.name " .
            " ORDER BY authorities.name";
        $count = $this->query($sql, $authority_group_id, $min_date, $max_date);
        foreach ($count as $count_info) {
            $result[$count_info['id']]['nb_transactions'] = $count_info['nb_transactions'];
        }
        return array_values($result);
    }

    public function isPesAcquitInCloud(int $object_id)
    {
        $sql = "SELECT pes_acquit_is_in_cloud FROM helios_transactions WHERE id=?";
        return $this->queryOne($sql, $object_id);
    }

    /**
     * @param $dateStatusCible
     * @param int $status_origine
     * @param int $status_cible
     * @return array
     * Retourne le nombre de transactions passés de $status_origine à $status_cible depuis $dateStatusCible
     * Cette fonction peut être perturbée par les modifications manuelles de status.
     */
    public function getStatusTransitionStatistics(
        $dateStatusCible,
        int $status_origine,
        int $status_cible,
        bool $isPasstrans = false
    ): array {
        $sql = "
    SELECT 
  COUNT(tw_cible.transaction_id) AS nb_transactions_cible, 
  AVG(tw_cible.date - tw_origine.date) AS delai_de_transmission_moyen,
  SUM(file_size) AS volume_transaction
FROM 
  helios_transactions_workflow AS tw_cible
  INNER JOIN helios_transactions_workflow AS tw_origine ON tw_cible.transaction_id = tw_origine.transaction_id 
  INNER JOIN helios_transactions ON helios_transactions.id = tw_cible.transaction_id
  INNER JOIN authorities ON helios_transactions.authority_id = authorities.id
WHERE 
  tw_cible.date > ? 
  AND tw_cible.status_id = ? 
  AND tw_origine.status_id = ?
AND authorities.helios_use_passtrans = ?
  ";

        $results = $this->query(
            $sql,
            $dateStatusCible,
            $status_cible,
            $status_origine,
            $isPasstrans ? "TRUE" : "FALSE"
        );
        return [
            $results[0]["nb_transactions_cible"],
            $results[0]["delai_de_transmission_moyen"],
            $results[0]["volume_transaction"]
        ];
    }

    /**
     * @param $date_status_cible
     * @return array
     */
    public function getNbPostesDepuis($date_status_cible, bool $isPasstrans = false): array
    {
        $sql = "SELECT COUNT(helios_transactions.id) AS nb_post_par_min, SUM(file_size) AS volume_transaction 
                    FROM helios_transactions_workflow
                    INNER JOIN helios_transactions ON helios_transactions.id = helios_transactions_workflow.transaction_id
                    INNER JOIN authorities ON helios_transactions.authority_id = authorities.id
                    WHERE date > ? AND status_id=1 AND authorities.helios_use_passtrans = ?; ";

        $results = $this->query($sql, $date_status_cible, $isPasstrans ? "TRUE" : "FALSE");
        return [$results[0]["nb_post_par_min"],$results[0]["volume_transaction"]];
    }

    /**
     * @param string|null $author_filter
     * @param bool $transmitted
     * @param bool $byMoth
     * @param bool $byYear
     * @return string
     */
    private function getWhereCondition(?string $author_filter, bool $transmitted, bool $byMoth, bool $byYear): string
    {
        $conditions = [];

        if ($author_filter != null) {
            // TODO : supprimer le AND de $author_filter
            $conditions[] = $author_filter;
        }
        if ($transmitted) {
            //3 = transmis
            //TODO : utiliser les constantes ...
            $conditions[] = "helios_transactions_workflow.status_id=3";
        }
        if ($byMoth) {
            $conditions[] = "helios_transactions.submission_date >='" . date('Y-m-01 00:00:00') . "'";
        } elseif ($byYear) {
            $conditions[] = "helios_transactions.submission_date >='" . date('Y-01-01 00:00:00') . "'";
        }

        if (empty($conditions)) {
            return '';
        }
        return " WHERE " . implode(' AND ', $conditions);
    }

    /**
     * this function is due to calculate the number of the transaction effective, return the number of the transactions.
     *
     * @param string $author_filter
     * @param bool $transmitted
     * @param bool $byMoth
     * @param bool $byYear
     * @return int
     * @throws \Exception
     */
    public function countTransactions($author_filter = null, $transmitted = false, $byMoth = false, $byYear = false): int
    {
        $sql = "SELECT COUNT ( DISTINCT helios_transactions.id ) AS nbtransactions FROM users " .
            " INNER JOIN helios_transactions ON users.id = helios_transactions.user_id " .
            " INNER JOIN helios_transactions_workflow ON helios_transactions.id = helios_transactions_workflow.transaction_id" .
            " INNER JOIN authorities ON helios_transactions.authority_id = authorities.id";

        $sql .= $this->getWhereCondition($author_filter, $transmitted, $byMoth, $byYear);

        return $this->query($sql)[0]['nbtransactions'];
    }

    /**
     * this function is due to connecte with db for the information of every transaction. and calculate the volume of the tranactions selected.
     *
     * @param string $author_filter
     * @param bool $transmitted
     * @param bool $byMoth
     * @param bool $byYear
     * @return int size of all transaction.
     * @throws \Exception
     */
    public function countTransactionVol(
        ?string $author_filter = null,
        bool $transmitted = false,
        bool $byMoth = false,
        bool $byYear = false
    ): int {
        $sql = "SELECT SUM ( helios_transactions.file_size ) AS voltransactions  FROM users " .
            " INNER JOIN helios_transactions ON users.id = helios_transactions.user_id " .
            " INNER JOIN helios_transactions_workflow ON helios_transactions.id = helios_transactions_workflow.transaction_id" .
            " INNER JOIN authorities ON helios_transactions.authority_id = authorities.id";

        $sql .= $this->getWhereCondition($author_filter, $transmitted, $byMoth, $byYear);

        return $this->query($sql)[0]['voltransactions'] ?? 0;
    }

    public function getListByStatusAndAuthority(
        int $status_id,
        int $authority_id,
        int $offset,
        int $limit,
        ?string $min_submission_date = null,
        ?string $max_submission_date = null
    ) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = 'SELECT helios_transactions.id FROM helios_transactions ';
        if ($min_submission_date !== null || $max_submission_date !== null) {
            $sql .= 'JOIN helios_transactions_workflow ON transaction_id=helios_transactions.id';
        }
        $sql .= ' WHERE last_status_id=? AND authority_id = ?';
        $data = [$status_id, $authority_id];
        if ($min_submission_date !== null) {
            $sql .= ' AND date >= ? ';
            $data[] = $min_submission_date;
        }
        if ($max_submission_date !== null) {
            $sql .= ' AND date <= ? ';
            $data[] = $max_submission_date;
        }
        if ($min_submission_date !== null || $max_submission_date !== null) {
            $sql .= 'AND status_id = ?';
            $data[] = $status_id;
        }
        $sql .= " ORDER BY helios_transactions.id DESC OFFSET $offset LIMIT $limit";
        return $this->query($sql, $data);
    }

    public function getRelativePath(string $transactionId): string
    {
        $acquitFileName = $this->queryOne("SELECT sha1 FROM helios_transactions WHERE id=?", $transactionId);

        if ($acquitFileName === false) {
            throw new FileNotFoundException('Aucun pes aller associé a la transaction ' . $transactionId);
        }

        return $acquitFileName;
    }

    public function getCloudId(string $transactionId): string
    {
        $transaction = $this->queryOne("SELECT sha1, siren FROM helios_transactions WHERE id=?", (int) $transactionId);

        if ($transaction === false) {
            throw new FileNotFoundException('Aucun pes aller associé a la transaction ' . $transactionId);
        }

        return $transaction['siren'] . '/' . basename($transaction['sha1']);
    }

    public function getTransactionIdFromFileName(string $filePath): ?string
    {
        $fileName = basename($filePath);
        $transactionId = $this->queryOne("SELECT id FROM helios_transactions WHERE sha1 LIKE ?", '%' . $fileName . '%');

        if (!$transactionId) {
            $transactionId = null;
        }

        return $transactionId;
    }

    public function setTransactionIsInCloud(string $transactionId): void
    {
        $this->setTransactionInCloud($transactionId);
    }

    /**
     * @param string $nomFich
     * @param string|null $codCol
     * @return int|null
     */

    public function findTransactionId(string $nomFich, ?string $codCol): ?int
    {
        if (is_null($codCol)) {
            $helios_transaction_list = $this->getIdByNomFic($nomFich);
        } else {
            $helios_transaction_list = $this->getIdByNomFicAndCodCol($nomFich, $codCol);
        }

        if (!$helios_transaction_list) {
            return null;
        }

        if (count($helios_transaction_list) == 1) {
            return $helios_transaction_list[0];
        }

        foreach ($helios_transaction_list as $transaction_id) {
            $workflow_info = $this->getLastStatusInfo($transaction_id);
            $transaction_list[] = [
                'transaction_id' => $transaction_id,
                'status_id' => $workflow_info['status_id'],
                'date' => $workflow_info['date']
            ];
        }

        usort($transaction_list, function ($a, $b) {
            if (
                $a['status_id'] == HeliosTransactionsSQL::TRANSMIS &&
                $b['status_id'] != HeliosTransactionsSQL::TRANSMIS
            ) {
                return -1;
            }
            if (
                $b['status_id'] == HeliosTransactionsSQL::TRANSMIS &&
                $a['status_id'] != HeliosTransactionsSQL::TRANSMIS
            ) {
                return 1;
            }
            if ($a['date'] > $b['date']) {
                return -1;
            } else {
                return 1;
            }
        });
        return $transaction_list[0]['transaction_id'];
    }
}
