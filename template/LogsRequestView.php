<?php

use S2lowLegacy\Model\LogsRequestData;

?>
<div id="content">
    <h1>Journal - Requête sur l'historique</h1>
    <p id="back-user-btn">
        <a href="/common/logs_view.php" class="btn btn-default" title="">
            Retour au journal
        </a>
    </p>


    <h2 id="demandes_desc">Demandes sur le journal</h2>

    <?php if (! $logs_request_list) : ?>
    <div class="alert alert-info">
        Aucune demande en attente ou archivée.
    </div>
    <?php endif; ?>

    <table class="table table-stripped" aria-describedby="demandes_desc">

        <tr>
            <th scope="col">Heure de la demande</th>
            <th scope="col">Heure du traitement</th>

            <th scope="col">Date début (journal)</th>
            <th scope="col">Date fin (journal)</th>

            <th scope="col">Etat</th>
            <th scope="col">Action</th>


        </tr>
        <?php foreach ($logs_request_list as $logs_request) : ?>
            <tr>
                <td><?php echo $fancyDate->getDateHeureFrancais($logs_request['date_demande']) ?></td>
                <td><?php echo $fancyDate->getDateHeureFrancais($logs_request['date_traitement']) ?></td>
                <td><?php echo $fancyDate->getDateFrancais($logs_request['date_debut']) ?></td>
                <td><?php echo $fancyDate->getDateFrancais($logs_request['date_fin']) ?></td>
                <td><?php echo LogsRequestData::getStateString($logs_request['state']) ?></td>
                <td>
                    <?php if ($logs_request['state'] == LogsRequestData::STATE_ASKING) :?>
                        <a
                                href="/common/logs_request_cancel.php?id=<?php echo $logs_request['id'] ?>"
                                class="btn btn-danger"
                        >
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;Supprimer
                        </a>
                    <?php endif; ?>
                    <?php if ($logs_request['state'] == LogsRequestData::STATE_AVAILABLE) :?>
                        <a
                                href="/common/logs_request_donwload.php?id=<?php echo $logs_request['id'] ?>"
                                class="btn btn-primary"
                        >
                            <span class="glyphicon glyphicon-download" aria-hidden="true">
                            </span>&nbsp;Télécharger
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>



    </table>


</div>

<h1></h1>