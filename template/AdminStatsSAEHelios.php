<?php

/** @var array $info_list */
/** @var array $status_list */

use S2lowLegacy\Class\helios\HeliosStatusSQL;

?>
<h1>Statistiques d'envoi au SAE - Helios </h1>
<p id="back-transaction-btn">
    <a href="/admin/stats-sae.php" class="btn btn-default">Retour statistiques globale SAE</a>
</p>

<h2>Détail par collectivité</h2>

<table class="data-table table table-striped ">

    <tr>
        <th>Collectivités</th>
        <?php foreach ($status_list as $status_id) : ?>
            <th>
                <?php hecho(HeliosStatusSQL::getStatusLibelle($status_id));?>
            </th>
        <?php endforeach; ?>
    </tr>
    <?php foreach ($info_list as $authority_id => $authority_info) :?>
    <tr>
        <th>
            <a href="/admin/authorities/admin_authority_sae_statistiques.php?id=<?php echo $authority_id?>">
                <?php hecho($authority_info['name']) ?>
            </a>
        </th>
        <?php foreach ($status_list as $status_id) : ?>
            <td>
                <span class="label label-<?php echo ($authority_info['status'][$status_id] ?? 0) ? "danger" : "success" ?>">
                    <?php echo $authority_info['status'][$status_id] ?? 0;?>
                </span>

            </td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
</table>

