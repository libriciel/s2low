<?php

class ActesTransactionsCloser
{
    private $actesTransactionsSQL;
    private $s2lowLogger;
    private $actesScriptHelper;

    public function __construct(
        ActesTransactionsSQL $actesTransactionsSQL,
        S2lowLogger $s2lowLogger,
        ActesScriptHelper $actesScriptHelper
    ) {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
        $this->s2lowLogger = $s2lowLogger;
        $this->actesScriptHelper = $actesScriptHelper;
    }

    public function closeAll()
    {
        $this->s2lowLogger->info("Passage du script de cloture de transaction");
        $date = date("Y-m-d", strtotime("-30 days"));
        $this->s2lowLogger->info("Toutes les transactions transmises avant le $date vont être fermés");
        $transaction_id_list = $this->actesTransactionsSQL->getByStatusSinceDate(
            ActesStatusSQL::STATUS_TRANSMIS,
            $date
        );
        $this->s2lowLogger->info(count($transaction_id_list) . " transactions ont été trouvé");
        foreach ($transaction_id_list as $transaction_id) {
            $this->s2lowLogger->info("Fermeture de la transaction $transaction_id");
            $this->actesScriptHelper->updateStatus(
                [$transaction_id],
                ActesStatusSQL::STATUS_EN_ERREUR,
                "Fermeture automatique de la transaction de plus de 30 jours"
            );
        }
    }
}
