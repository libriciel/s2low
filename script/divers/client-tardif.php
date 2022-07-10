<?php

//Liste les clients qui utilisent S2low entre minuit et six heures du matin

require_once(__DIR__ . "/../../init/init.php");
$sqlQuery = LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);


class ConnexionTardiveSQL
{
    private $sqlQuery;

    public function __construct(SQLQuery $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    private function maxDate()
    {
        $sql = "SELECT max(date) FROM helios_transactions_workflow";
        $max_date = $this->sqlQuery->queryOne($sql);
        return $max_date;
    }

    public function getTransactionLastDays($num_days = 30)
    {
        $date = $this->maxDate();
        $result = array();
        for ($i = 0; $i < $num_days; $i++) {
            $date = date("Y-m-d", strtotime("$date -1day"));
            $result = array_merge($result, $this->getTransactionListOneDay($date));
        }
        return $result;
    }

    public function getTransactionListOneDay($date)
    {
        $result = $this->getAll('actes', $date);
        $result = array_merge($result, $this->getAll('helios', $date));
        return $result;
    }

    private function getAll($module, $date)
    {
        $time = strtotime($date);
        $date_begin = date('Y-m-d 00:00:00', $time);
        $date_end = date('Y-m-d 06:00:00', $time);

        $sql = "SELECT '$date' as date,'$module' as module,req1.name as authority_name,req1.email as authority_email, authority_groups.name as authority_groups,COUNT(*) as nb FROM ( " .
                "SELECT authorities.name,authorities.authority_group_id, users.email " .
                " FROM {$module}_transactions_workflow " .
                " JOIN {$module}_transactions ON {$module}_transactions_workflow.transaction_id={$module}_transactions.id " .
                " JOIN authorities ON  {$module}_transactions.authority_id=authorities.id " .
                " JOIN users ON {$module}_transactions.user_id=users.id " .
                " WHERE {$module}_transactions_workflow.date>=? AND {$module}_transactions_workflow.date<? AND status_id=1 ";
        if ($module == 'actes') {
            $sql .= " AND type != '7'";
        }
        $sql .= ") as req1 " .
                " LEFT JOIN authority_groups ON req1.authority_group_id = authority_groups.id " .
                " GROUP BY req1.name,authority_groups.name,req1.email;";

        return $this->sqlQuery->query($sql, $date_begin, $date_end);
    }
}

$connexionTardiveSQL = new ConnexionTardiveSQL($sqlQuery);

$result = $connexionTardiveSQL->getTransactionLastDays(30);
foreach ($result as $id => $line) {
    echo implode("; ", $line) . "\n";
}
